<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Application\Administration\PrivilegeGuard;
use App\Application\Administration\SuperAdminGuard;
use App\Application\Auth\RefreshTokenService;
use App\Domain\Identity\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignUserRolesRequest;
use App\Http\Requests\ChangeUserStatusRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Infrastructure\Http\ApiResponse;
use App\Models\User;
use App\Support\Http\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class UserController extends Controller
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokens,
        private readonly PrivilegeGuard $privileges,
        private readonly SuperAdminGuard $superAdmins,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(...[AllowedFilter::partial('name'), AllowedFilter::exact('email'), AllowedFilter::exact('status')])
            ->allowedSorts(...['name', 'email', 'status', 'created_at'])
            ->allowedIncludes(...['roles'])
            ->allowedFields(...['id', 'name', 'email', 'status', 'email_verified_at', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate(Pagination::perPage(request(), 20));

        return ApiResponse::paginated(UserResource::collection($users->items()), [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $data = $request->safe()->except('roles');
        $data['status'] ??= UserStatus::Active->value;
        $roles = $request->validated('roles', []);
        abort_unless($this->privileges->canAssignRoles($request->user(), $roles), 403);
        $user = DB::transaction(function () use ($data, $roles): User {
            $user = User::query()->create($data);
            $user->syncRoles($roles);

            return $user;
        });
        activity('administration')->causedBy($request->user())->performedOn($user)->event('created')->log('User created');

        return ApiResponse::success(new UserResource($user->load('roles')), 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success(new UserResource($user->load('roles', 'permissions')));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $data = $request->safe()->only(['name', 'email', 'password']);
        $emailChanged = array_key_exists('email', $data) && $data['email'] !== $user->email;
        $credentialsChanged = array_key_exists('password', $data) || $emailChanged;

        DB::transaction(function () use (&$user, $data, $credentialsChanged, $emailChanged): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $this->authorize('update', $user);
            $user->update($data);
            if ($emailChanged) {
                $user->forceFill([
                    'email_verified_at' => config('api.require_email_verification') ? null : now(),
                    'status' => config('api.require_email_verification') ? UserStatus::Pending : $user->status,
                ])->save();
            }
            if ($credentialsChanged) {
                $this->refreshTokens->revokeAll($user, 'credentials_changed_by_administrator');
            }
        });
        activity('administration')->causedBy($request->user())->performedOn($user)->event('updated')->log('User updated');

        return ApiResponse::success(new UserResource($user->load('roles')));
    }

    public function status(ChangeUserStatusRequest $request, User $user): JsonResponse
    {
        $this->authorize('changeStatus', $user);
        $status = UserStatus::from($request->string('status')->toString());
        DB::transaction(function () use (&$user, $status): void {
            $this->superAdmins->assertCanDeactivate($user, $status);
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $this->authorize('changeStatus', $user);
            $user->forceFill(['status' => $status])->save();
            if (! $status->mayAuthenticate()) {
                $this->refreshTokens->revokeAll($user, 'status_changed');
            }
        });
        activity('administration')->causedBy($request->user())->performedOn($user)->event('status_changed')->log('User status changed');

        return ApiResponse::success(new UserResource($user->load('roles')));
    }

    public function roles(AssignUserRolesRequest $request, User $user): JsonResponse
    {
        $this->authorize('assignRoles', $user);
        $roles = $request->validated('roles');
        abort_unless($this->privileges->canAssignRoles($request->user(), $roles), 403);
        DB::transaction(function () use (&$user, $roles): void {
            $this->superAdmins->assertCanRemoveRoleOrDelete($user, $roles);
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $this->authorize('assignRoles', $user);
            $user->syncRoles($roles);
        });
        activity('administration')->causedBy($request->user())->performedOn($user)->event('roles_assigned')->log('User roles assigned');

        return ApiResponse::success(new UserResource($user->load('roles')));
    }

    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);
        DB::transaction(function () use (&$user): void {
            $this->superAdmins->assertCanRemoveRoleOrDelete($user);
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $this->authorize('delete', $user);
            $this->refreshTokens->revokeAll($user, 'account_deleted');
            $user->delete();
        });
        activity('administration')->causedBy(request()->user())->performedOn($user)->event('deleted')->log('User deleted');

        return response()->noContent();
    }
}

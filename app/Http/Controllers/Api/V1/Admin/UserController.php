<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Application\Auth\RefreshTokenService;
use App\Domain\Identity\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Http\Resources\UserResource;
use App\Infrastructure\Http\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class UserController extends Controller
{
    public function __construct(private readonly RefreshTokenService $refreshTokens) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(...[AllowedFilter::partial('name'), AllowedFilter::exact('email'), AllowedFilter::exact('status')])
            ->allowedSorts(...['name', 'email', 'status', 'created_at'])
            ->allowedIncludes(...['roles'])
            ->allowedFields(...['id', 'name', 'email', 'status', 'email_verified_at', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate(min((int) request('per_page', 20), 100));

        return ApiResponse::success(UserResource::collection($users->items()), meta: [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ]);
    }

    public function store(AdminRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $data = $request->safe()->except('roles');
        $data['status'] ??= UserStatus::Active->value;
        $user = User::query()->create($data);
        $user->syncRoles($request->validated('roles', []));
        activity('administration')->causedBy($request->user())->performedOn($user)->event('created')->log('User created');

        return ApiResponse::success(new UserResource($user->load('roles')), 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success(new UserResource($user->load('roles', 'permissions')));
    }

    public function update(AdminRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $user->update($request->safe()->only(['name', 'email', 'password']));
        activity('administration')->causedBy($request->user())->performedOn($user)->event('updated')->log('User updated');

        return ApiResponse::success(new UserResource($user->load('roles')));
    }

    public function status(AdminRequest $request, User $user): JsonResponse
    {
        $this->authorize('changeStatus', $user);
        $status = UserStatus::from($request->string('status')->toString());
        $user->forceFill(['status' => $status])->save();
        if (! $status->mayAuthenticate()) {
            $this->refreshTokens->revokeAll($user, 'status_changed');
        }
        activity('administration')->causedBy($request->user())->performedOn($user)->event('status_changed')->log('User status changed');

        return ApiResponse::success(new UserResource($user->load('roles')));
    }

    public function roles(AdminRequest $request, User $user): JsonResponse
    {
        $this->authorize('assignRoles', $user);
        $roles = $request->validated('roles');
        abort_if(
            $user->hasRole('super-admin') && ! in_array('super-admin', $roles, true)
                && User::query()->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))->count() <= 1,
            409,
            'The final super-admin role cannot be removed.',
        );
        $user->syncRoles($roles);
        activity('administration')->causedBy($request->user())->performedOn($user)->event('roles_assigned')->log('User roles assigned');

        return ApiResponse::success(new UserResource($user->load('roles')));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $user->delete();
        activity('administration')->causedBy(request()->user())->performedOn($user)->event('deleted')->log('User deleted');

        return ApiResponse::success(null, 204);
    }
}

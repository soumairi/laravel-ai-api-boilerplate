<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Application\Administration\PrivilegeGuard;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRolePermissionsRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Infrastructure\Http\ApiResponse;
use App\Support\Http\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class RoleController extends Controller
{
    public function __construct(private readonly PrivilegeGuard $privileges) {}

    public function index(): JsonResponse
    {
        $this->authorize('roles.viewAny');
        $roles = QueryBuilder::for(Role::class)->allowedFilters(...[AllowedFilter::partial('name')])
            ->allowedSorts(...['name', 'created_at'])->allowedIncludes(...['permissions'])
            ->allowedFields(...['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->paginate(Pagination::perPage(request(), 20));

        return ApiResponse::paginated($roles->items(), ['total' => $roles->total()]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('roles.create');
        abort_unless($request->user()?->hasRole('super-admin') === true, 403);
        $role = DB::transaction(function () use ($request): Role {
            $role = Role::query()->create(['name' => $request->string('name')->toString(), 'guard_name' => 'api']);
            $role->syncPermissions($request->validated('permissions', []));

            return $role;
        });
        activity('administration')->causedBy($request->user())->performedOn($role)->event('created')->log('Role created');

        return ApiResponse::success($role->load('permissions'), 201);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('roles.view');

        return ApiResponse::success($role->load('permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('roles.update');
        abort_unless($this->privileges->canManageRole($request->user(), $role), 403);
        DB::transaction(function () use ($request, $role): void {
            $role->update($request->safe()->only('name'));
        });
        activity('administration')->causedBy($request->user())->performedOn($role)->event('updated')->log('Role updated');

        return ApiResponse::success($role->load('permissions'));
    }

    public function permissions(AssignRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $this->authorize('roles.assignPermissions');
        abort_unless(
            $this->privileges->canAssignPermissions($request->user(), $role, $request->validated('permissions')),
            403,
        );
        DB::transaction(static fn () => $role->syncPermissions($request->validated('permissions')));
        activity('administration')->causedBy($request->user())->performedOn($role)->event('permissions_assigned')->log('Role permissions assigned');

        return ApiResponse::success($role->load('permissions'));
    }

    public function destroy(Role $role): Response
    {
        $this->authorize('roles.delete');
        abort_if($this->privileges->isSystemRole($role), 409, 'System roles cannot be deleted.');
        abort_unless(request()->user()?->hasRole('super-admin') === true, 403);
        activity('administration')->causedBy(request()->user())->performedOn($role)->event('deleted')->log('Role deleted');
        $role->delete();

        return response()->noContent();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Infrastructure\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('roles.viewAny');
        $roles = QueryBuilder::for(Role::class)->allowedFilters(...[AllowedFilter::partial('name')])
            ->allowedSorts(...['name', 'created_at'])->allowedIncludes(...['permissions'])
            ->allowedFields(...['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->paginate(min((int) request('per_page', 20), 100));

        return ApiResponse::success($roles->items(), meta: ['total' => $roles->total()]);
    }

    public function store(AdminRequest $request): JsonResponse
    {
        $this->authorize('roles.create');
        $role = Role::query()->create(['name' => $request->string('name')->toString(), 'guard_name' => 'api']);
        $role->syncPermissions($request->validated('permissions', []));
        activity('administration')->causedBy($request->user())->performedOn($role)->event('created')->log('Role created');

        return ApiResponse::success($role->load('permissions'), 201);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('roles.view');

        return ApiResponse::success($role->load('permissions'));
    }

    public function update(AdminRequest $request, Role $role): JsonResponse
    {
        $this->authorize('roles.update');
        abort_if($role->name === 'super-admin' && $request->has('name'), 409, 'The system role cannot be renamed.');
        $role->update($request->safe()->only('name'));
        if ($request->has('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        }
        activity('administration')->causedBy($request->user())->performedOn($role)->event('updated')->log('Role updated');

        return ApiResponse::success($role->load('permissions'));
    }

    public function permissions(AdminRequest $request, Role $role): JsonResponse
    {
        $this->authorize('roles.assignPermissions');
        $role->syncPermissions($request->validated('permissions'));
        activity('administration')->causedBy($request->user())->performedOn($role)->event('permissions_assigned')->log('Role permissions assigned');

        return ApiResponse::success($role->load('permissions'));
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('roles.delete');
        abort_if($role->name === 'super-admin', 409, 'The system role cannot be deleted.');
        activity('administration')->causedBy(request()->user())->performedOn($role)->event('deleted')->log('Role deleted');
        $role->delete();

        return ApiResponse::success(null, 204);
    }
}

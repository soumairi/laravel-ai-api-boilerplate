<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('permissions.viewAny');
        $permissions = QueryBuilder::for(Permission::class)->allowedFilters(...[AllowedFilter::partial('name')])
            ->allowedSorts(...['name', 'created_at'])
            ->allowedFields(...['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->paginate(min((int) request('per_page', 50), 100));

        return ApiResponse::success($permissions->items(), meta: ['total' => $permissions->total()]);
    }
}

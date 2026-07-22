<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->where('guard_name', 'api')
            ->whereIn('name', config('rbac.permissions'))
            ->get();
        $adminPermissions = $permissions->whereIn('name', (array) config('rbac.admin_permissions'));

        Role::findOrCreate('super-admin', 'api')->syncPermissions($permissions);
        Role::findOrCreate('admin', 'api')->syncPermissions($adminPermissions);
        Role::findOrCreate((string) config('rbac.default_role'), 'api')->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

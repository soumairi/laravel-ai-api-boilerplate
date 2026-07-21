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

        Role::findOrCreate('super-admin', 'api')->syncPermissions($permissions);
        Role::findOrCreate('admin', 'api')->syncPermissions($permissions->reject(
            static fn (Permission $permission): bool => $permission->name === 'roles.delete',
        ));
        Role::findOrCreate((string) config('rbac.default_role'), 'api')->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

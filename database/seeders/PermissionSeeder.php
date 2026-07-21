<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('rbac.permissions') as $name) {
            Permission::findOrCreate((string) $name, 'api');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

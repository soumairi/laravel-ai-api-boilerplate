<?php

declare(strict_types=1);

return [
    'default_role' => 'user',
    'system_roles' => ['super-admin', 'admin', 'user'],
    'protected_roles' => ['super-admin', 'admin'],
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
    'permissions' => [
        'users.viewAny', 'users.view', 'users.create', 'users.update', 'users.delete',
        'users.changeStatus', 'users.assignRoles',
        'roles.viewAny', 'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        'roles.assignPermissions',
        'permissions.viewAny',
        'api.documentation.view',
        'horizon.view',
    ],
    'admin_permissions' => [
        'users.viewAny', 'users.view', 'users.create', 'users.update', 'users.delete',
        'users.changeStatus', 'users.assignRoles',
        'api.documentation.view',
        'horizon.view',
    ],
];

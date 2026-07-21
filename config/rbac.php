<?php

declare(strict_types=1);

return [
    'default_role' => 'user',
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
    'permissions' => [
        'users.viewAny', 'users.view', 'users.create', 'users.update', 'users.delete',
        'users.restore', 'users.changeStatus', 'users.assignRoles',
        'roles.viewAny', 'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        'roles.assignPermissions',
        'permissions.viewAny', 'permissions.assign',
        'audit.viewAny', 'health.view',
        'api.documentation.view',
        'horizon.view',
    ],
];

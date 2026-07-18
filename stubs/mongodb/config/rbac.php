<?php

use Modules\Rbac\Models\MongoPermission;
use Modules\Rbac\Models\MongoRole;

return [
    'storage' => 'mongodb',

    'models' => [
        'user' => null,
        'role' => MongoRole::class,
        'permission' => MongoPermission::class,
    ],

    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'users' => 'users',
        'role_user' => 'role_user',
        'permission_role' => 'permission_role',
    ],

    'protected_role' => 'admin',
];

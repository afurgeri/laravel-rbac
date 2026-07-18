<?php

use Modules\Rbac\Models\Permission;
use Modules\Rbac\Models\Role;

return [
    'storage' => 'mysql',

    'models' => [
        'user' => null,
        'role' => Role::class,
        'permission' => Permission::class,
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

<?php

return [
    'models' => [
        'user' => 'App\\Models\\User',
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

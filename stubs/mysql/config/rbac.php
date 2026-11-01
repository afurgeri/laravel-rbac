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

    /*
     * The first administrator created by AdminUserSeeder.
     */
    'admin_user' => [
        'name' => env('RBAC_ADMIN_NAME', 'Admin'),
        'email' => env('RBAC_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('RBAC_ADMIN_PASSWORD', 'password'),
    ],

    /*
     * Readable names for what each permission prefix covers, shown on the role screens.
     * Add one entry per resource, e.g. 'customers' => 'Customers'. Values are translation keys.
     */
    'permission_subjects' => [
        'roles' => 'Roles',
        'users' => 'Users',
    ],

    /*
     * Names for actions other than view, create, update and delete. Keys may be an action
     * suffix ('export') or a full permission name ('leads.convert'). Values are translation keys.
     */
    'permission_actions' => [
        'export' => 'Export',
    ],
];

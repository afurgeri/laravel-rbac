# Laravel RBAC

Roles, permissions, role assignment, and permission middleware for Laravel applications. The package depends on [`afurgeri/laravel-crud`](https://github.com/afurgeri/laravel-crud) for reusable CRUD definitions.

## Scope

This package provides the reusable RBAC core:

- SQL and MongoDB role and permission models;
- `HasRoles` and `HasPermissions` integration contracts;
- permission middleware;
- configurable RBAC tables and user model;
- migrations and permission seeders;
- role authorization through Laravel policies;
- optional CRUD integration for the `Role` model.

Resource-specific user CRUD, Inertia controllers, routes, navigation, and Vue pages belong in the consuming application.

## Installation

```bash
composer require afurgeri/laravel-rbac
```

Both the RBAC and CRUD service providers are registered through Laravel package discovery.

After installing both packages, generate the application integration with:

```bash
php artisan crud:install
php artisan rbac:install --database=mysql
```

`rbac:install` generates the user/role CRUD definitions, controllers, policy, routes, and Inertia/Vue pages. It requires the generic CRUD frontend files from `crud:install` and reports the required `User` model integration without modifying the model automatically.

For MongoDB, install the official driver in the consuming application and select the MongoDB integration stubs:

```bash
composer require mongodb/laravel-mongodb
php artisan rbac:install --database=mongodb
```

The installer generates explicit `Role` and `Permission` model configuration for the selected connector. It does not generate or modify the application's authenticatable `User` model.

If Packagist is temporarily unavailable, add the GitHub repositories as temporary VCS sources:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/afurgeri/laravel-crud"
        },
        {
            "type": "vcs",
            "url": "https://github.com/afurgeri/laravel-rbac"
        }
    ]
}
```

Then require the tagged versions:

```bash
composer require afurgeri/laravel-crud:^0.4 afurgeri/laravel-rbac:^0.3
```

## Full MongoDB application

Selecting `--database=mongodb` configures the RBAC role and permission models, but it cannot replace the application's `User` model automatically. For a fully MongoDB-backed application, the authenticatable model must use the MongoDB authentication base class and the RBAC trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Rbac\Concerns\HasRoles;
use Modules\Rbac\Contracts\HasPermissions;
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable implements HasPermissions
{
    use HasFactory, HasRoles;

    protected $connection = 'mongodb';

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }
}
```

The essential changes compared with the default Laravel model are:

- Replace `Illuminate\Foundation\Auth\User` with `MongoDB\Laravel\Auth\User`.
- Keep `HasRoles` and `HasPermissions` on the same model used by the configured auth provider.
- Set the `mongodb` connection and the `users` collection/table.
- Store `name`, `email`, `password`, `remember_token`, and any fields required by the application's authentication features in the user document.
- Keep the password hashed and hidden. Do not store a plain-text password.
- Add `role_ids` as an array of role document IDs. The package's MongoDB relationship reads this field from the user document.

The application must also point both Laravel authentication and RBAC to this model:

```php
// config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],

// config/rbac.php
'storage' => 'mongodb',
'models' => [
    'user' => App\Models\User::class,
    'role' => Modules\Rbac\Models\MongoRole::class,
    'permission' => Modules\Rbac\Models\MongoPermission::class,
],
```

If the application uses Fortify, keep its normal provider and actions, but verify every action that creates or updates users uses the MongoDB model. Password reset storage also needs the MongoDB integration provider; the standard SQL password-reset migration is not enough for a full MongoDB setup. Passkeys, two-factor authentication, sessions, and API tokens may require their own MongoDB-compatible storage configuration.

## User model integration

The application's authenticatable model must use `HasRoles` and implement `HasPermissions`:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Rbac\Concerns\HasRoles;
use Modules\Rbac\Contracts\HasPermissions;

class User extends Authenticatable implements HasPermissions
{
    use HasRoles;
}
```

Configure the application user model in `config/rbac.php`. The installer writes the explicit `role` and `permission` classes for its selected connector, but leaves `models.user` unset because it must point to the consuming application's authenticatable model:

```php
return [
    'models' => [
        'user' => App\Models\User::class,
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
```

Publish the configuration and migrations when they need to be customized:

```bash
php artisan vendor:publish --tag=rbac-config
php artisan vendor:publish --tag=rbac-migrations
php artisan migrate
```

## Permissions

Use `hasPermission()` in application authorization logic:

```php
if ($user->hasPermission('reports.view')) {
    // ...
}
```

Protect routes with the registered `permission` middleware:

```php
Route::get('/reports', ReportController::class)
    ->middleware('permission:reports.view');
```

Application-specific permission constants should be declared by the application or a dependent package. The bundled `RbacPermissions` list contains the permissions used by the companion scaffold.

## CRUD integration

`Role` implements the CRUD definition bridge and can be used with `CrudIndexManager`, `CrudSchemaManager`, and `CrudMutationManager` from `afurgeri/laravel-crud`.

The package does not provide user CRUD because user fields, password handling, policies, routes, and frontend pages differ between applications.

## MongoDB behavior

MongoDB uses `MongoRole` and `MongoPermission` with `permission_ids`, `role_ids`, and user `role_ids` arrays instead of SQL pivot tables. The package migration owns only the `roles` and `permissions` collections; the consuming application owns its `users` collection and its indexes.

The relationship is represented in both directions:

| Document | Field | Meaning |
| --- | --- | --- |
| `users` | `role_ids` | IDs of roles assigned to the user |
| `roles` | `permission_ids` | IDs of permissions assigned to the role |
| `roles` | `user_ids` | IDs of users assigned to the role |
| `permissions` | `role_ids` | IDs of roles containing the permission |

Use the relationship methods rather than writing these arrays manually:

```php
$user->roles()->sync([$role->getKey()]);
$role->permissions()->sync([$permission->getKey()]);

$user->hasPermission('reports.view');
```

The MongoDB installer sets `storage` to `mongodb` and configures `MongoRole::class` and `MongoPermission::class`. Configure the application's MongoDB `User` model, set `models.user`, and create an index on `users.role_ids` when role-based lookups need to scale. The SQL installer instead configures `Role::class` and `Permission::class` and uses the configured pivot tables.

The application owns the `users` collection and should create its indexes explicitly. At minimum, consider unique `email` and a multikey index on `role_ids`:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::connection('mongodb')->table('users', static function (Blueprint $blueprint): void {
    $blueprint->unique('email');
    $blueprint->index('role_ids');
});
```

MongoDB document IDs are commonly `ObjectId` values. Treat route and frontend IDs as strings at the application boundary, while passing the original model keys to Eloquent relationships. Do not mix SQL pivot migrations with the MongoDB RBAC setup.

MongoDB integration tests are separate from the default SQL suite. From the package repository, install the optional test dependency, enable the MongoDB PHP extension, start MongoDB, and run:

```bash
composer require mongodb/laravel-mongodb:^5.8 --dev
composer test:mongodb
```

## Service provider behavior

The provider automatically:

- merges the default `rbac` configuration;
- loads RBAC migrations and JSON translations;
- registers the `permission` middleware alias;
- exposes configuration and migration publish tags.

It does not register web routes or Inertia pages.

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
composer require afurgeri/laravel-crud:^0.2 afurgeri/laravel-rbac:^0.1
```

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

Configure the model and table names in the application's `config/rbac.php`. The installer writes the explicit `role` and `permission` classes for its selected connector:

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

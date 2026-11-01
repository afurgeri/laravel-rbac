<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Rbac\Database\Seeders\AdminRoleSeeder;
use Modules\Rbac\Database\Seeders\RbacPermissionSeeder;
use Modules\Rbac\Models\Permission;
use Modules\Rbac\Models\Role;
use Modules\Rbac\RbacPermissions;

beforeEach(function () {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
    });
});

test('the provider loads the default configuration and migrations', function () {
    expect(config('rbac.tables.roles'))->toBe('roles')
        ->and(config('rbac.tables.permissions'))->toBe('permissions');

    $this->artisan('migrate')->assertExitCode(0);

    expect(Schema::hasTable('roles'))->toBeTrue()
        ->and(Schema::hasTable('permissions'))->toBeTrue()
        ->and(Schema::hasTable('role_user'))->toBeTrue()
        ->and(Schema::hasTable('permission_role'))->toBeTrue();
});

test('the SQL seeders remain idempotent', function () {
    $this->artisan('migrate')->assertExitCode(0);

    $this->seed(RbacPermissionSeeder::class);
    $this->seed(RbacPermissionSeeder::class);
    $this->seed(AdminRoleSeeder::class);
    $this->seed(AdminRoleSeeder::class);

    $admin = Role::query()->where('name', 'admin')->firstOrFail();

    expect(Permission::query()->count())->toBe(count(RbacPermissions::all()))
        ->and($admin->permissions)->toHaveCount(count(RbacPermissions::all()));
});

test('the migration adds the active flag to the users table once', function () {
    $this->artisan('migrate')->assertExitCode(0);
    $this->artisan('migrate:rollback')->assertExitCode(0);
    $this->artisan('migrate')->assertExitCode(0);

    expect(Schema::hasColumn('users', 'is_active'))->toBeTrue();
});

test('the migration keeps an active flag the application already added', function () {
    Schema::table('users', function (Blueprint $table): void {
        $table->boolean('is_active')->default(false);
    });

    $this->artisan('migrate')->assertExitCode(0);

    expect(Schema::getColumns('users'))->toHaveCount(2);
});

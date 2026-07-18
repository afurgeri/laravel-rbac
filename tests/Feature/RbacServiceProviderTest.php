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

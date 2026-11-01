<?php

use Modules\Rbac\Database\Seeders\AdminRoleSeeder;
use Modules\Rbac\Database\Seeders\AdminUserSeeder;
use Modules\Rbac\Database\Seeders\RbacPermissionSeeder;
use Modules\Rbac\Models\Role;
use Modules\Rbac\RbacPermissions;
use Tests\Fixtures\User;

beforeEach(fn () => migrateWithUsers());

test('it creates the configured administrator with every permission', function () {
    config(['rbac.admin_user' => ['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret-password']]);

    $this->seed([RbacPermissionSeeder::class, AdminRoleSeeder::class, AdminUserSeeder::class]);

    $admin = User::query()->where('email', 'owner@example.com')->firstOrFail();

    expect($admin->name)->toBe('Owner')
        ->and(password_verify('secret-password', $admin->password))->toBeTrue()
        ->and($admin->roles->pluck('name')->all())->toBe(['admin'])
        ->and($admin->permissionNames())->toEqualCanonicalizing(RbacPermissions::all());
});

test('running it again keeps a single administrator and its other roles', function () {
    $this->seed([RbacPermissionSeeder::class, AdminRoleSeeder::class, AdminUserSeeder::class]);
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $otherRole = Role::query()->create(['name' => 'sales']);
    $admin->roles()->attach($otherRole);

    $this->seed(AdminUserSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and($admin->roles()->pluck('name')->sort()->values()->all())->toBe(['admin', 'sales']);
});

test('it requires the protected role to exist', function () {
    $this->seed(AdminUserSeeder::class);
})->throws(RuntimeException::class, 'Run AdminRoleSeeder before AdminUserSeeder');

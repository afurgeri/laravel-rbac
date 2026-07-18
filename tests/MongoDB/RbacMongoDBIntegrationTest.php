<?php

use Illuminate\Http\Request;
use Modules\Rbac\Crud\RoleCrudDefinition;
use Modules\Rbac\Database\Seeders\AdminRoleSeeder;
use Modules\Rbac\Database\Seeders\RbacPermissionSeeder;
use Modules\Rbac\Http\Middleware\EnsureUserHasPermission;
use Modules\Rbac\Models\MongoPermission;
use Modules\Rbac\Models\MongoRole;
use Modules\Rbac\Policies\RolePolicy;
use Modules\Rbac\RbacModels;
use Modules\Rbac\RbacPermissions;
use Symfony\Component\HttpFoundation\Response;
use Tests\MongoDB\Fixtures\MongoRbacUser;
use Tests\MongoDbTestCase;

uses(MongoDbTestCase::class);

beforeEach(function () {
    MongoRbacUser::query()->delete();
    MongoRole::query()->delete();
    MongoPermission::query()->delete();
});

afterEach(function () {
    MongoRbacUser::query()->delete();
    MongoRole::query()->delete();
    MongoPermission::query()->delete();
});

test('it assigns MongoDB permissions to roles and roles to users', function () {
    $permission = MongoPermission::query()->create(['name' => 'reports.view']);
    $role = MongoRole::query()->create(['name' => 'reporter']);
    $role->permissions()->sync([$permission->getKey()]);

    $user = MongoRbacUser::query()->create(['name' => 'Ada']);
    $user->roles()->sync([$role->getKey()]);

    expect($user->hasPermission('reports.view'))->toBeTrue()
        ->and($user->hasPermission('reports.edit'))->toBeFalse()
        ->and($user->roles)->toHaveCount(1)
        ->and($user->roles->first()->permissions)->toHaveCount(1)
        ->and($role->users)->toHaveCount(1)
        ->and($role->users->first()->getKey())->toBe($user->getKey());
});

test('it runs RBAC seeders idempotently with MongoDB models', function () {
    $this->seed(RbacPermissionSeeder::class);
    $this->seed(RbacPermissionSeeder::class);
    $this->seed(AdminRoleSeeder::class);
    $this->seed(AdminRoleSeeder::class);

    $admin = MongoRole::query()->where('name', 'admin')->firstOrFail();

    expect(MongoPermission::query()->count())->toBe(count(RbacPermissions::all()))
        ->and($admin->permissions)->toHaveCount(count(RbacPermissions::all()));
});

test('it resolves MongoDB RBAC models from configuration', function () {
    expect(RbacModels::role())->toBe(MongoRole::class)
        ->and(RbacModels::permission())->toBe(MongoPermission::class);
});

test('it treats missing MongoDB relationship arrays as empty relations', function () {
    $role = MongoRole::query()->create(['name' => 'empty']);
    $permission = MongoPermission::query()->create(['name' => 'empty.view']);
    $user = MongoRbacUser::query()->create(['name' => 'Ada']);

    expect(MongoRole::query()->with('permissions')->find($role->getKey())->permissions)->toBeEmpty()
        ->and(MongoPermission::query()->with('roles')->find($permission->getKey())->roles)->toBeEmpty()
        ->and($user->roles)->toBeEmpty()
        ->and(MongoRbacUser::query()->with('roles')->find($user->getKey())->roles)->toBeEmpty();
});

test('it preserves permissions when eager loading the CRUD role columns', function () {
    $permission = MongoPermission::query()->create(['name' => 'reports.view']);
    $role = MongoRole::query()->create(['name' => 'reporter']);
    $role->permissions()->sync([$permission->getKey()]);

    $roles = MongoRole::query()
        ->with((new RoleCrudDefinition)->eagerLoads())
        ->paginate(15);

    expect($roles->first()->permissions)->toHaveCount(1)
        ->and($roles->first()->permissions->first()->name)->toBe('reports.view');
});

test('it enforces role policies with MongoDB-backed permissions', function () {
    $permission = MongoPermission::query()->create(['name' => RbacPermissions::UPDATE_ROLES]);
    $role = MongoRole::query()->create(['name' => 'editor']);
    $role->permissions()->sync([$permission->getKey()]);

    $user = MongoRbacUser::query()->create(['name' => 'Ada']);
    $user->roles()->sync([$role->getKey()]);

    $policy = new RolePolicy;

    expect($policy->viewAny($user))->toBeFalse()
        ->and($policy->create($user))->toBeFalse()
        ->and($policy->update($user, $role))->toBeTrue()
        ->and($policy->delete($user, $role))->toBeFalse();

    $admin = MongoRole::query()->create(['name' => 'admin']);

    expect($policy->update($user, $admin))->toBeFalse()
        ->and($policy->delete($user, $admin))->toBeFalse();
});

test('it enforces permissions through middleware for a MongoDB-backed user', function () {
    $permission = MongoPermission::query()->create(['name' => RbacPermissions::VIEW_ROLES]);
    $role = MongoRole::query()->create(['name' => 'viewer']);
    $role->permissions()->sync([$permission->getKey()]);

    $user = MongoRbacUser::query()->create(['name' => 'Ada']);
    $user->roles()->sync([$role->getKey()]);

    $request = Request::create('/roles');
    $request->setUserResolver(fn (): MongoRbacUser => $user);

    $response = (new EnsureUserHasPermission)->handle(
        $request,
        fn (): Response => new Response('allowed'),
        RbacPermissions::VIEW_ROLES,
    );

    expect($response->getContent())->toBe('allowed');
});

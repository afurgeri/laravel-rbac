<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\User;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

/**
 * Creates the application users table, runs the package migrations against it,
 * and makes the fixture user the authenticatable model.
 */
function migrateWithUsers(): void
{
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });

    test()->artisan('migrate')->assertExitCode(0);

    config(['auth.providers.users.model' => User::class]);
}

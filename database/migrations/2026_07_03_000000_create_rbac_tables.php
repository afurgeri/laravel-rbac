<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = (string) config('rbac.tables.roles', 'roles');
        $permissions = (string) config('rbac.tables.permissions', 'permissions');
        $permissionRole = (string) config('rbac.tables.permission_role', 'permission_role');
        $roleUser = (string) config('rbac.tables.role_user', 'role_user');
        $userTable = (string) config('rbac.tables.users', 'users');

        Schema::create($roles, function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create($permissions, function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create($permissionRole, function (Blueprint $table) use ($permissions, $roles): void {
            $table->foreignId('permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->foreignId('role_id')->constrained($roles)->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create($roleUser, function (Blueprint $table) use ($roles, $userTable): void {
            $table->foreignId('role_id')->constrained($roles)->cascadeOnDelete();
            $table->foreignId('user_id')->constrained($userTable)->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('rbac.tables.role_user', 'role_user'));
        Schema::dropIfExists(config('rbac.tables.permission_role', 'permission_role'));
        Schema::dropIfExists(config('rbac.tables.permissions', 'permissions'));
        Schema::dropIfExists(config('rbac.tables.roles', 'roles'));
    }
};

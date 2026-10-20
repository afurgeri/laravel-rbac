<?php

use App\Http\Controllers\Rbac\RoleController;
use App\Http\Controllers\Rbac\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Rbac\Models\Role;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::crudResource('roles', RoleController::class, Role::class);
    Route::patch('users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');
    Route::crudResource('users', UserController::class, User::class);
});

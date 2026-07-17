<?php

use App\Http\Controllers\Rbac\RoleController;
use App\Http\Controllers\Rbac\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('roles', RoleController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
});

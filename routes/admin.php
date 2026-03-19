<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureAdmin::class])
    ->prefix('admin')
    ->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::post('/users/{user}/toggle-admin', [UserManagementController::class, 'toggleAdmin'])->name('admin.users.toggle-admin');
    });


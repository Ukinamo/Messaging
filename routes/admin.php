<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureAdmin::class])
    ->prefix('admin')
    ->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('admin.users.create');
        Route::get('/users/peek', [UserManagementController::class, 'peek'])->name('admin.users.peek');
        Route::post('/users', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('/users/{user}/toggle-admin', [UserManagementController::class, 'toggleAdmin'])->name('admin.users.toggle-admin');
    });


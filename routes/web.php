<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

Route::middleware(['auth','verified'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    require __DIR__.'/rental_system.php';
    require __DIR__.'/orders.php';
    require __DIR__.'/customers.php';

    Route::prefix('admin')->group(function () {
        // Benutzerverwaltung
        Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:user.list');
            Route::get('/create', 'create')->name('create')->middleware('permission:user.add');
            Route::post('/', 'store')->name('store')->middleware('permission:user.add');

            Route::get('/{user}/edit', 'edit')->name('edit')->middleware('permission:user.edit');
            Route::put('/{user}', 'update')->name('update')->middleware('permission:user.edit');

            Route::delete('/{user}', 'destroy')->name('destroy')->middleware('permission:user.delete');
        });

        // Rollenverwaltung
        Route::controller(RoleController::class)->prefix('roles')->name('roles.')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:role.list');
            Route::get('/create', 'create')->name('create')->middleware('permission:role.add');
            Route::post('/', 'store')->name('store')->middleware('permission:role.add');

            Route::get('/{role}/edit', 'edit')->name('edit')->middleware('permission:role.edit');
            Route::put('/{role}', 'update')->name('update')->middleware('permission:role.edit');

            Route::delete('/{role}', 'destroy')->name('destroy')->middleware('permission:role.delete');
        });

        // Berechtigungsverwaltung
        Route::controller(PermissionController::class)->prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:perm.list');
            Route::get('/create', 'create')->name('create')->middleware('permission:perm.add');
            Route::post('/', 'store')->name('store')->middleware('permission:perm.add');

            Route::get('/{permission}/edit', 'edit')->name('edit')->middleware('permission:perm.edit');
            Route::put('/{permission}', 'update')->name('update')->middleware('permission:perm.edit');

            Route::delete('/{permission}', 'destroy')->name('destroy')->middleware('permission:perm.delete');
        });
    });
});

Route::middleware(['auth'])->group(function () {
    Route::controller(ProfileController::class)->name('profile.')->group(function () {
        Route::get('/profile', 'edit')->name('edit');
        Route::patch('/profile', 'update')->name('update');
        Route::delete('/profile', 'destroy')->name('destroy');
    });
});


require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\Contact\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('contacts')->name('contacts.')->group(function () {
    Route::get('/', [ContactController::class, 'index'])
        ->middleware('permission:contacts.view')
        ->name('index');

    Route::get('/create', [ContactController::class, 'create'])
        ->middleware('permission:contacts.create')
        ->name('create');

    Route::post('/', [ContactController::class, 'store'])
        ->middleware('permission:contacts.create')
        ->name('store');

    Route::get('/{contact}', [ContactController::class, 'show'])
        ->middleware('permission:contacts.view')
        ->name('show');

    Route::get('/{contact}/edit', [ContactController::class, 'edit'])
        ->middleware('permission:contacts.edit')
        ->name('edit');

    Route::put('/{contact}', [ContactController::class, 'update'])
        ->middleware('permission:contacts.edit')
        ->name('update');

    Route::delete('/{contact}', [ContactController::class, 'destroy'])
        ->middleware('permission:contacts.delete')
        ->name('destroy');

    Route::patch('/{contact}/toggle-status', [ContactController::class, 'toggleStatus'])
        ->middleware('permission:contacts.toggle-status')
        ->name('toggle-status');
});

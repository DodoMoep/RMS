<?php

use App\Http\Controllers\Admin\WorkInstructionCategoryController;
use App\Http\Controllers\Admin\WorkInstructionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/work-instructions')
    ->name('work-instructions.')
    ->middleware('permission:work-instructions.manage')
    ->group(function () {

        // Kategorien (Tätigkeiten)
        Route::get('/', [WorkInstructionCategoryController::class, 'index'])->name('categories.index');
        Route::get('/create', [WorkInstructionCategoryController::class, 'create'])->name('categories.create');
        Route::post('/', [WorkInstructionCategoryController::class, 'store'])->name('categories.store');
        Route::get('/{category}/edit', [WorkInstructionCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/{category}', [WorkInstructionCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/{category}', [WorkInstructionCategoryController::class, 'destroy'])->name('categories.destroy');

        // Anweisungen (innerhalb einer Kategorie)
        Route::get('/{category}/instructions/create', [WorkInstructionController::class, 'create'])->name('instructions.create');
        Route::post('/{category}/instructions', [WorkInstructionController::class, 'store'])->name('instructions.store');
        Route::get('/{category}/instructions/{instruction}/edit', [WorkInstructionController::class, 'edit'])->name('instructions.edit');
        Route::put('/{category}/instructions/{instruction}', [WorkInstructionController::class, 'update'])->name('instructions.update');
        Route::delete('/{category}/instructions/{instruction}', [WorkInstructionController::class, 'destroy'])->name('instructions.destroy');
    });

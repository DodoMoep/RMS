<?php

use App\Http\Controllers\HallController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\ProtocolController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory-items')->group(function () {
    Route::get('/', [InventoryItemController::class, 'index'])->name('inventory-items.index');
    Route::get('/create', [InventoryItemController::class, 'create'])->name('inventory-items.create');
    Route::post('/', [InventoryItemController::class, 'store'])->name('inventory-items.store');
    Route::get('/{inventoryItem}/edit', [InventoryItemController::class, 'edit'])->name('inventory-items.edit');
    Route::put('/{inventoryItem}', [InventoryItemController::class, 'update'])->name('inventory-items.update');
    Route::delete('/{inventoryItem}', [InventoryItemController::class, 'destroy'])->name('inventory-items.destroy');
});

Route::prefix('tenants')->group(function () {
    Route::get('/', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::delete('/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
});

Route::prefix('halls')->group(function () {
    Route::get('/', [HallController::class, 'index'])->name('halls.index');
    Route::get('/create', [HallController::class, 'create'])->name('halls.create');
    Route::post('/', [HallController::class, 'store'])->name('halls.store');
    Route::get('/{hall}/edit', [HallController::class, 'edit'])->name('halls.edit');
    Route::put('/{hall}', [HallController::class, 'update'])->name('halls.update');
    Route::delete('/{hall}', [HallController::class, 'destroy'])->name('halls.destroy');
});

Route::prefix('protocols')->group(function () {
    Route::get('/', [ProtocolController::class,'index'])->name('protocols.index');
    Route::get('/{protocol}', [ProtocolController::class,'showForm'])->name('protocol.form');
    Route::post('/{protocol}/save', [ProtocolController::class,'saveForm'])->name('protocol.save');
    Route::post('/{protocol}/sign', [ProtocolController::class,'sign'])->name('protocol.sign');
    Route::get('/{protocol}/pdf', [ProtocolController::class,'pdf'])->name('protocol.pdf');
});

Route::prefix('rentals')->group(function () {
    Route::get('/', [RentalController::class, 'index'])->name('rentals.index');
    Route::get('/create', [RentalController::class, 'create'])->name('rentals.create');
    Route::post('/', [RentalController::class, 'store'])->name('rentals.store');
    Route::get('/{rental}/edit', [RentalController::class, 'edit'])->name('rentals.edit');
    Route::put('/{rental}', [RentalController::class, 'update'])->name('rentals.update');
    Route::delete('/{rental}', [RentalController::class, 'destroy'])->name('rentals.destroy');
    
    // Protokoll-Aktionen direkt aus Vermietung
    Route::post('/{rental}/handover', [RentalController::class,'createOrOpenHandover'])->name('rentals.handover');
    Route::post('/{rental}/return', [RentalController::class,'createOrOpenReturn'])->name('rentals.return');
});

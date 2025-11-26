<?php

use App\Http\Controllers\Rental\HallController;
use App\Http\Controllers\Inventory\InventoryItemController;
use App\Http\Controllers\Logistics\ProtocolController;
use App\Http\Controllers\Rental\RentalController;
use App\Http\Controllers\Rental\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory-items')->middleware(['auth', 'permission:inventory.view'])->group(function () {
    Route::get('/', [InventoryItemController::class, 'index'])->name('inventory-items.index');
    Route::get('/create', [InventoryItemController::class, 'create'])->middleware('permission:inventory.create')->name('inventory-items.create');
    Route::post('/', [InventoryItemController::class, 'store'])->middleware('permission:inventory.create')->name('inventory-items.store');
    Route::get('/{inventoryItem}/edit', [InventoryItemController::class, 'edit'])->middleware('permission:inventory.edit')->name('inventory-items.edit');
    Route::put('/{inventoryItem}', [InventoryItemController::class, 'update'])->middleware('permission:inventory.edit')->name('inventory-items.update');
    Route::delete('/{inventoryItem}', [InventoryItemController::class, 'destroy'])->middleware('permission:inventory.delete')->name('inventory-items.destroy');
});

Route::prefix('tenants')->middleware(['auth', 'permission:tenants.view'])->group(function () {
    Route::get('/', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/create', [TenantController::class, 'create'])->middleware('permission:tenants.create')->name('tenants.create');
    Route::post('/', [TenantController::class, 'store'])->middleware('permission:tenants.create')->name('tenants.store');
    Route::get('/{tenant}/edit', [TenantController::class, 'edit'])->middleware('permission:tenants.edit')->name('tenants.edit');
    Route::put('/{tenant}', [TenantController::class, 'update'])->middleware('permission:tenants.edit')->name('tenants.update');
    Route::delete('/{tenant}', [TenantController::class, 'destroy'])->middleware('permission:tenants.delete')->name('tenants.destroy');
});

Route::prefix('halls')->middleware(['auth', 'permission:halls.view'])->group(function () {
    Route::get('/', [HallController::class, 'index'])->name('halls.index');
    Route::get('/create', [HallController::class, 'create'])->middleware('permission:halls.create')->name('halls.create');
    Route::post('/', [HallController::class, 'store'])->middleware('permission:halls.create')->name('halls.store');
    Route::get('/{hall}/edit', [HallController::class, 'edit'])->middleware('permission:halls.edit')->name('halls.edit');
    Route::put('/{hall}', [HallController::class, 'update'])->middleware('permission:halls.edit')->name('halls.update');
    Route::delete('/{hall}', [HallController::class, 'destroy'])->middleware('permission:halls.delete')->name('halls.destroy');
});

Route::prefix('protocols')->middleware(['auth', 'permission:protocols.view'])->group(function () {
    Route::get('/', [ProtocolController::class,'index'])->name('protocols.index');
    Route::get('/{protocol}', [ProtocolController::class,'showForm'])->middleware('permission:protocols.create')->name('protocol.form');
    Route::post('/{protocol}/save', [ProtocolController::class,'saveForm'])->middleware('permission:protocols.edit')->name('protocol.save');
    Route::post('/{protocol}/sign', [ProtocolController::class,'sign'])->middleware('permission:protocols.sign')->name('protocol.sign');
    Route::get('/{protocol}/pdf', [ProtocolController::class,'pdf'])->middleware('permission:protocols.pdf')->name('protocol.pdf');
});

Route::prefix('rentals')->middleware(['auth', 'permission:rentals.view'])->group(function () {
    Route::get('/', [RentalController::class, 'index'])->name('rentals.index');
    Route::get('/create', [RentalController::class, 'create'])->middleware('permission:rentals.create')->name('rentals.create');
    Route::post('/', [RentalController::class, 'store'])->middleware('permission:rentals.create')->name('rentals.store');
    Route::get('/{rental}/edit', [RentalController::class, 'edit'])->middleware('permission:rentals.edit')->name('rentals.edit');
    Route::put('/{rental}', [RentalController::class, 'update'])->middleware('permission:rentals.edit')->name('rentals.update');
    Route::delete('/{rental}', [RentalController::class, 'destroy'])->middleware('permission:rentals.delete')->name('rentals.destroy');
    
    // Protokoll-Aktionen direkt aus Vermietung
    Route::post('/{rental}/handover', [RentalController::class,'createOrOpenHandover'])->middleware('permission:rentals.handover')->name('rentals.handover');
    Route::post('/{rental}/return', [RentalController::class,'createOrOpenReturn'])->middleware('permission:rentals.return')->name('rentals.return');
});

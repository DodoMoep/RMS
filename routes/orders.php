<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\OrderAnalyticsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPackingController;
use Illuminate\Support\Facades\Route;

// Articles Management
Route::prefix('articles')->middleware(['auth', 'permission:orders.create'])->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
});

// Order Management
Route::prefix('orders')->middleware(['auth', 'permission:orders.view'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/create', [OrderController::class, 'create'])->middleware('permission:orders.create')->name('orders.create');
    Route::post('/', [OrderController::class, 'store'])->middleware('permission:orders.create')->name('orders.store');
    Route::get('/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/{order}/edit', [OrderController::class, 'edit'])->middleware('permission:orders.edit')->name('orders.edit');
    Route::put('/{order}', [OrderController::class, 'update'])->middleware('permission:orders.edit')->name('orders.update');
    Route::delete('/{order}', [OrderController::class, 'destroy'])->middleware('permission:orders.delete')->name('orders.destroy');
    Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/{order}/history', [OrderController::class, 'history'])->middleware('permission:orders.view-history')->name('orders.history');
});

// Packing Interface (for packers)
Route::prefix('packing')->middleware(['auth', 'permission:orders.pack'])->group(function () {
    Route::get('/', [OrderPackingController::class, 'index'])->name('packing.index');
    Route::get('/{order}', [OrderPackingController::class, 'show'])->name('packing.show');
    Route::post('/items/{orderItem}/pack', [OrderPackingController::class, 'packItem'])->name('packing.pack-item');
    Route::delete('/items/{orderItem}/unpack', [OrderPackingController::class, 'unpackItem'])->name('packing.unpack-item');
    Route::post('/{order}/complete', [OrderPackingController::class, 'completeOrder'])->name('packing.complete');
});

// Delivery Notes
Route::prefix('delivery-notes')->middleware(['auth', 'permission:orders.print'])->group(function () {
    Route::post('/{order}/generate', [DeliveryNoteController::class, 'generate'])->name('delivery-notes.generate');
    Route::post('/{order}/print', [DeliveryNoteController::class, 'print'])->name('delivery-notes.print');
    Route::get('/{order}/download', [DeliveryNoteController::class, 'download'])->name('delivery-notes.download');
});

// Analytics Dashboard
Route::prefix('analytics')->middleware(['auth', 'permission:analytics.view'])->group(function () {
    Route::get('/', [OrderAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/export', [OrderAnalyticsController::class, 'exportReport'])->middleware('permission:analytics.export')->name('analytics.export');
    
    // API endpoints for charts
    Route::get('/api/orders-by-status', [OrderAnalyticsController::class, 'ordersByStatus'])->name('analytics.api.status');
    Route::get('/api/orders-over-time', [OrderAnalyticsController::class, 'ordersOverTime'])->name('analytics.api.timeline');
    Route::get('/api/top-items', [OrderAnalyticsController::class, 'topItems'])->name('analytics.api.items');
    Route::get('/api/packer-performance', [OrderAnalyticsController::class, 'packerPerformance'])->name('analytics.api.packers');
});

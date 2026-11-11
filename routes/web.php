<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InventoryItemController::class, 'dashboard'])->name('dashboard');
Route::resource('categories', CategoryController::class)->except('show');
Route::resource('locations', LocationController::class)->except('show');
Route::resource('inventory', InventoryItemController::class)
    ->parameters(['inventory' => 'item']);

Route::prefix('laporan')->name('reports.')->group(function () {
    Route::get('barang', [ReportController::class, 'items'])->name('items');
    Route::get('barang/export/{format}', [ReportController::class, 'exportItems'])
        ->whereIn('format', ['xlsx', 'docx', 'pdf'])
        ->name('items.export');

    Route::get('kir', [ReportController::class, 'kir'])->name('kir');
    Route::get('kir/export/{format}', [ReportController::class, 'exportKir'])
        ->whereIn('format', ['xlsx', 'docx', 'pdf'])
        ->name('kir.export');

    Route::get('keadaan', [ReportController::class, 'conditions'])->name('conditions');
    Route::get('keadaan/export/{format}', [ReportController::class, 'exportConditions'])
        ->whereIn('format', ['xlsx', 'docx', 'pdf'])
        ->name('conditions.export');
});

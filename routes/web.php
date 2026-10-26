<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryItemController;

Route::get('/', [InventoryItemController::class, 'dashboard'])->name('dashboard');
Route::resource('categories', CategoryController::class)->except('show');
Route::resource('inventory', InventoryItemController::class)
    ->parameters(['inventory' => 'item']);

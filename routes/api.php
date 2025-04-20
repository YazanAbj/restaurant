<?php

use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::apiResource('inventory', InventoryItemController::class)->middleware('auth:sanctum');
Route::apiResource('menu', MenuItemController::class)->middleware('auth:sanctum');
Route::apiResource('reservations', ReservationController::class)->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);
    Route::get('/reports/inventory-status', [ReportController::class, 'inventoryStatus']);
});

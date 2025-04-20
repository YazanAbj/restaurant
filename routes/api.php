<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('menu', MenuItemController::class)->middleware('auth:sanctum');
Route::apiResource('reservations', ReservationController::class)->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);
    Route::get('/reports/inventory-status', [ReportController::class, 'inventoryStatus']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('inventory-items', InventoryItemController::class);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

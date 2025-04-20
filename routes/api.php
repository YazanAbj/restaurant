<?php

use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
<<<<<<< HEAD



Route::apiResource('inventory', InventoryItemController::class)->middleware('auth:sanctum');
Route::apiResource('menu', MenuItemController::class)->middleware('auth:sanctum');
Route::apiResource('reservations', ReservationController::class)->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);
    Route::get('/reports/inventory-status', [ReportController::class, 'inventoryStatus']);
=======
use App\Http\Controllers\Api\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
>>>>>>> 6f7c30371792ab9f75b23fe24105586b2571ca19
});

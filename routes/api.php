<?php

use App\Http\Controllers\Manager\InventoryController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\Manager\MenuController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsManager;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);
    Route::post('/reservations/{id}', [ReservationController::class, 'update']);
});

//manager
Route::prefix('manager/reservations')->namespace('App\Http\Controllers\Manager')->middleware('auth:sanctum', IsManager::class)->group(function () {
    Route::post('/', [App\Http\Controllers\Manager\ReservationController::class, 'store']);
    Route::post('/{id}/cancel', [App\Http\Controllers\Manager\ReservationController::class, 'cancel']);
    Route::post('/{id}', [App\Http\Controllers\Manager\ReservationController::class, 'update']);
    Route::get('/', [App\Http\Controllers\Manager\ReservationController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\Manager\ReservationController::class, 'show']);
    Route::post('/{id}/accept', [App\Http\Controllers\Manager\ReservationController::class, 'accept']);
    Route::post('/{id}/reject', [App\Http\Controllers\Manager\ReservationController::class, 'reject']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);
    Route::get('/reports/inventory-status', [ReportController::class, 'inventoryStatus']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});


Route::middleware('auth:sanctum', IsManager::class)->group(function () {
    Route::get('/tables', [TableController::class, 'index']);
    Route::post('/tables', [TableController::class, 'store']);
    Route::post('/tables/{id}', [TableController::class, 'update']);
});


//manager tables
Route::prefix('manager/tables')->namespace('App\Http\Controllers\Manager')->middleware('auth:sanctum', IsManager::class)->group(function () {
    Route::get('/', [App\Http\Controllers\Manager\TableController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Manager\TableController::class, 'store']);
    Route::post('/{id}', [App\Http\Controllers\Manager\TableController::class, 'update']);
    Route::post('/{id}/status', [App\Http\Controllers\Manager\TableController::class, 'updateStatus']);
    Route::get('/statuses', [App\Http\Controllers\Manager\TableController::class, 'getTablesByStatus']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('menu-items', [MenuItemController::class, 'index']);
    Route::get('menu-items/{id}', [MenuItemController::class, 'show']);
    Route::post('menu-items', [MenuItemController::class, 'store']);
    Route::post('menu-items/{id}/update', [MenuItemController::class, 'update']);
    Route::delete('menu-items/{id}', [MenuItemController::class, 'destroy']);
});


///////////////////// manager ////////////////////////////////////


Route::prefix('manager/inventory')->namespace('App\Http\Controllers\Manager')->middleware('auth:sanctum', IsManager::class)->group(function () {
    Route::get('/', [InventoryController::class, 'index']);
    Route::post('/', [InventoryController::class, 'store']);
    Route::get('/{id}', [InventoryController::class, 'show']);
    Route::post('/{id}', [InventoryController::class, 'update']);
    Route::delete('/{id}', [InventoryController::class, 'destroy']);
    Route::patch('/{id}/low-stock', [InventoryController::class, 'setLowStock']);
});

Route::prefix('manager/menu')->namespace('App\Http\Controllers\Manager')->middleware('auth:sanctum', IsManager::class)->group(function () {
    Route::get('/', [MenuController::class, 'index']);
    Route::post('/', [MenuController::class, 'store']);
    Route::get('/{id}', [MenuController::class, 'show']);
    Route::post('/{id}', [MenuController::class, 'update']);
    Route::delete('/{id}', [MenuController::class, 'destroy']);
});


///////////////////// manager end ////////////////////////////////////
Route::prefix('orders')->group(function () {
    Route::post('/start', [OrderController::class, 'startOrder']); // create order
    Route::post('/{order}/add-items', [OrderController::class, 'addItem']); // add items
    Route::post('/{order}/send', [OrderController::class, 'sendToChef']); // send to chef
    Route::get('/kitchen', [OrderController::class, 'kitchenOrders']); // kitchen view
});

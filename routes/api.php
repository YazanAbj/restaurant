<?php
use App\Http\Controllers\Manager\InventoryController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\Manager\MenuController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);





Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/{id}', [ReservationController::class, 'show']);
    Route::post('/reservations/{id}', [ReservationController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);
    Route::get('/reports/inventory-status', [ReportController::class, 'inventoryStatus']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tables', [TableController::class, 'index']);
    Route::post('/tables', [TableController::class, 'store']);
    Route::post('/tables/{id}', [TableController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('menu-items', [MenuItemController::class, 'index']);
    Route::get('menu-items/{id}', [MenuItemController::class, 'show']);
    Route::post('menu-items', [MenuItemController::class, 'store']);
    Route::post('menu-items/{id}/update', [MenuItemController::class, 'update']);
    Route::delete('menu-items/{id}', [MenuItemController::class, 'destroy']);
});


///////////////////// manager ////////////////////////////////////


Route::prefix('manager/inventory')->namespace('App\Http\Controllers\Manager')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [InventoryController::class, 'index']);
    Route::post('/', [InventoryController::class, 'store']);
    Route::get('/{id}', [InventoryController::class, 'show']);
    Route::post('/{id}', [InventoryController::class, 'update']);
    Route::delete('/{id}', [InventoryController::class, 'destroy']);
    Route::patch('/{id}/low-stock', [InventoryController::class, 'setLowStock']);
});

Route::prefix('manager/menu')->namespace('App\Http\Controllers\Manager')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [MenuController::class, 'index']);
    Route::post('/', [MenuController::class, 'store']);
    Route::get('/{id}', [MenuController::class, 'show']);
    Route::post('/{id}', [MenuController::class, 'update']);
    Route::delete('/{id}', [MenuController::class, 'destroy']);


});






///////////////////// manager end ////////////////////////////////////


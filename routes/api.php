<?php

use App\Http\Controllers\Manager\InventoryController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\Kitchen\KitchenSectionController;
use App\Http\Controllers\Manager\MenuController;
use App\Http\Controllers\Manager\OrderController;
use App\Http\Controllers\Manager\BillController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsManager;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



//manager
Route::prefix('manager/reservations')->middleware('auth:sanctum')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::post('/', [App\Http\Controllers\Manager\ReservationController::class, 'store']);
    Route::post('/{id}', [App\Http\Controllers\Manager\ReservationController::class, 'update']);
    Route::get('/', [App\Http\Controllers\Manager\ReservationController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\Manager\ReservationController::class, 'show']);
    Route::patch('/{id}/status', [App\Http\Controllers\Manager\ReservationController::class, 'updateStatus']);
    Route::post('/{id}/cancel', [App\Http\Controllers\Manager\ReservationController::class, 'cancel']);
    Route::delete('/{id}', [App\Http\Controllers\Manager\ReservationController::class, 'destroy']);
});




//manager tables
Route::prefix('manager/tables')->middleware('auth:sanctum')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::post('/', [App\Http\Controllers\Manager\TableController::class, 'store']);
    Route::post('/{id}', [App\Http\Controllers\Manager\TableController::class, 'update']);
    Route::post('/{id}/status', [App\Http\Controllers\Manager\TableController::class, 'updateStatus']);
    Route::get('/statuses', [App\Http\Controllers\Manager\TableController::class, 'getTablesByStatus']);
});


Route::prefix('/manager/orders')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Http\Controllers\Manager\OrderController::class, 'index']);
    Route::get('/{order}', [App\Http\Controllers\Manager\OrderController::class, 'show']);
    Route::post('/', [App\Http\Controllers\Manager\OrderController::class, 'store']);
    Route::post('/bills/{bill}/close', [App\Http\Controllers\Manager\OrderController::class, 'closeBill']);
    Route::post('/{order}', [App\Http\Controllers\Manager\OrderController::class, 'update']);
    Route::delete('/{order}', [App\Http\Controllers\Manager\OrderController::class, 'destroy']);
    Route::put('/{order}/cancel', [App\Http\Controllers\Manager\OrderController::class, 'cancel']);
    Route::put('/order-items/{orderItem}', [App\Http\Controllers\Manager\OrderController::class, 'updateOrderItem']);
    Route::delete('/order-items/{orderItemId}', [App\Http\Controllers\Manager\OrderController::class, 'destroyOrderItem']);
    Route::patch('/order-items/{orderItemId}/cancel', [App\Http\Controllers\Manager\OrderController::class, 'cancelOrderItem']);
});

Route::get('/manager/kitchen/sections/{id}/queue', [KitchenSectionController::class, 'queue']);
Route::post('/manager/kitchen/order-items/{id}/ready', [KitchenSectionController::class, 'markItemReady']);
Route::get('/manager/kitchen/sections/{id}/ready-items', [KitchenSectionController::class, 'readyItems']);
Route::get('/manager/kitchen/sections/{id}/items-by-status', [KitchenSectionController::class, 'itemsByStatus']);
Route::post('/manager/kitchen-sections', [KitchenSectionController::class, 'store']);
Route::get('/manager/kitchen-sections', [KitchenSectionController::class, 'index']);
Route::get('/manager/kitchen-sections/{id}', [KitchenSectionController::class, 'show']);
Route::put('/manager/kitchen-sections/{id}', [KitchenSectionController::class, 'update']);
Route::delete('/manager/kitchen-sections/{id}', [KitchenSectionController::class, 'destroy']);


///////////////////// manager ////////////////////////////////////

Route::prefix('manager/inventory')->middleware('auth:sanctum')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::get('/', [InventoryController::class, 'index']);
    Route::get('/low-stock', [InventoryController::class, 'lowStockItems']);
    Route::post('/', [InventoryController::class, 'store']);
    Route::get('/{id}', [InventoryController::class, 'show']);
    Route::post('/{id}', [InventoryController::class, 'update']);
    Route::delete('/{id}', [InventoryController::class, 'destroy']);
    Route::post('{id}/subtract', [InventoryController::class, 'subtractQuantity']);
    Route::patch('{id}/low-stock', [InventoryController::class, 'setLowStock']);
});



Route::prefix('manager/bills')->middleware('auth:sanctum')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::get('/', [App\Http\Controllers\Manager\BillController::class, 'index']);
    Route::get('/status/{status}', [App\Http\Controllers\Manager\BillController::class, 'filterByStatus']);
    Route::get('/{bill}', [App\Http\Controllers\Manager\BillController::class, 'show']);
    Route::post('/{bill}/discount', [App\Http\Controllers\Manager\BillController::class, 'applyDiscount']);
    Route::delete('/{bill}', [App\Http\Controllers\Manager\BillController::class, 'destroy']);
});

Route::prefix('manager/staff')->middleware('auth:sanctum')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::get('/', [App\Http\Controllers\Manager\StaffController::class, 'index']);
    Route::get('/bonus', [App\Http\Controllers\Manager\StaffController::class, 'bonusindex']);
    Route::get('/{staff}', [App\Http\Controllers\Manager\StaffController::class, 'show']);
    Route::post('/', [App\Http\Controllers\Manager\StaffController::class, 'store']);
    Route::post('/{staff}', [App\Http\Controllers\Manager\StaffController::class, 'update']);
    Route::delete('/{staff}', [App\Http\Controllers\Manager\StaffController::class, 'destroy']);
    Route::post('/{staff}/bonus', [App\Http\Controllers\Manager\StaffController::class, 'applyBonus']);
    Route::put('/bonus/{bonusHistory}', [App\Http\Controllers\Manager\StaffController::class, 'updateBonus']);
    Route::delete('/bonus/{bonusHistory}', [App\Http\Controllers\Manager\StaffController::class, 'deleteBonus']);
});

Route::prefix('manager/menu')->middleware('auth:sanctum')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::get('/', [App\Http\Controllers\Manager\MenuController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\Manager\MenuController::class, 'show']);
    Route::post('/', [App\Http\Controllers\Manager\MenuController::class, 'store']);
    Route::post('/{id}', [App\Http\Controllers\Manager\MenuController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Manager\MenuController::class, 'destroy']);
});

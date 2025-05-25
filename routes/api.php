<?php

use App\Http\Controllers\Manager\InventoryController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\Kitchen\KitchenSectionController;
use App\Http\Controllers\Manager\MenuController;
use App\Http\Controllers\Manager\OrderController;
use App\Http\Controllers\Manager\BillController;
use App\Http\Controllers\Manager\ReportController;
use App\Http\Controllers\Manager\ReservationController;
use App\Http\Controllers\Manager\StaffController;
use App\Http\Controllers\Manager\TableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsManager;



Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::put('/users/{id}', [AuthController::class, 'updateUser']);
    Route::get('/users', [AuthController::class, 'index']);
    Route::get('/users/hidden', [AuthController::class, 'hidden']);
    Route::get('/users/{id}', [AuthController::class, 'show']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::delete('/users/{id}', [AuthController::class, 'softDelete']);
    Route::post('/users/{id}/restore', [AuthController::class, 'restore']);
    Route::delete('/users/{id}/force', [AuthController::class, 'forceDelete']);
});


Route::prefix('manager/reservations')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::post('/', [App\Http\Controllers\Manager\ReservationController::class, 'store']);
    Route::post('/{id}', [App\Http\Controllers\Manager\ReservationController::class, 'update']);
    Route::get('/', [App\Http\Controllers\Manager\ReservationController::class, 'index']);
    Route::get('/hidden', [App\Http\Controllers\Manager\ReservationController::class, 'hidden']);
    Route::get('/{id}', [App\Http\Controllers\Manager\ReservationController::class, 'show']);
    Route::patch('/{id}/status', [App\Http\Controllers\Manager\ReservationController::class, 'updateStatus']);
    Route::post('/{id}/cancel', [App\Http\Controllers\Manager\ReservationController::class, 'cancel']);


    Route::delete('/{id}', [ReservationController::class, 'softDelete']);
    Route::delete('/{id}/force', [ReservationController::class, 'forceDelete']);
    Route::patch('/{id}/restore', [ReservationController::class, 'restore']);
});


Route::prefix('manager/tables')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::post('/', [App\Http\Controllers\Manager\TableController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Manager\TableController::class, 'show']);
    Route::post('/{id}', [App\Http\Controllers\Manager\TableController::class, 'update']);
    Route::post('/{id}/status', [App\Http\Controllers\Manager\TableController::class, 'updateStatus']);
    Route::get('/', [App\Http\Controllers\Manager\TableController::class, 'getTablesByStatus']);
    Route::get('/index/hidden', [App\Http\Controllers\Manager\TableController::class, 'hidden']);

    Route::delete('/{id}', [TableController::class, 'softDelete']);
    Route::delete('/{id}/force', [TableController::class, 'forceDelete']);
    Route::patch('/{id}/restore', [TableController::class, 'restore']);
});


Route::prefix('/manager/orders')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Http\Controllers\Manager\OrderController::class, 'index']);
    Route::get('/hidden', [App\Http\Controllers\Manager\OrderController::class, 'hidden']);
    Route::get('/by-table/{tableNumber}', [OrderController::class, 'getOrdersByTableNumber']);
    Route::get('/{order}', [App\Http\Controllers\Manager\OrderController::class, 'show']);
    Route::post('/', [App\Http\Controllers\Manager\OrderController::class, 'store']);
    Route::post('/bills/{bill}/close', [App\Http\Controllers\Manager\OrderController::class, 'closeBill']);
    Route::post('/{order}', [App\Http\Controllers\Manager\OrderController::class, 'update']);
    Route::put('/{order}/cancel', [App\Http\Controllers\Manager\OrderController::class, 'cancel']);
    Route::put('/order-items/{orderItem}', [App\Http\Controllers\Manager\OrderController::class, 'updateOrderItem']);
    Route::patch('/order-items/{orderItemId}/cancel', [App\Http\Controllers\Manager\OrderController::class, 'cancelOrderItem']);
    Route::get('/order-items/by-status', [OrderController::class, 'getItemsByStatus']);
    Route::get('/filter/by-bill-status', [OrderController::class, 'filterOrdersByBillStatus']);
    Route::patch('/order-items/{id}/prepare', [OrderController::class, 'setPreparing']);

    // Order 
    Route::delete('/{id}', [OrderController::class, 'softDelete']);
    Route::delete('/{id}/force', [OrderController::class, 'forceDelete']);
    Route::patch('/{id}/restore', [OrderController::class, 'restoreOrder']);

    // Order Items 
    Route::delete('/order-items/{id}', [OrderController::class, 'softDeleteOrderItem']);
    Route::delete('/order-items/{id}/force', [OrderController::class, 'forceDeleteOrderItem']);
    Route::patch('/order-items/{id}/restore', [OrderController::class, 'restoreOrderItem']);
});

Route::get('/manager/kitchen/sections/{id}/queue', [KitchenSectionController::class, 'queue']);
Route::get('/manager/kitchen/sections/hidden', [KitchenSectionController::class, 'hidden']);
Route::post('/manager/kitchen/order-items/{id}/ready', [KitchenSectionController::class, 'markItemReady']);
Route::get('/manager/kitchen/sections/{id}/ready-items', [KitchenSectionController::class, 'readyItems']);
Route::get('/manager/kitchen/sections/{id}/items-by-status', [KitchenSectionController::class, 'itemsByStatus']);
Route::post('/manager/kitchen-sections', [KitchenSectionController::class, 'store']);
Route::get('/manager/kitchen-sections', [KitchenSectionController::class, 'index']);
Route::get('/manager/kitchen-sections/{id}', [KitchenSectionController::class, 'show']);
Route::put('/manager/kitchen-sections/{id}', [KitchenSectionController::class, 'update']);

Route::delete('/manager/kitchen-sections/{id}', [KitchenSectionController::class, 'softDelete']);
Route::patch('/manager/kitchen-sections/{id}/restore', [KitchenSectionController::class, 'restore']);
Route::delete('/manager/kitchen-sections/{id}/force', [KitchenSectionController::class, 'forceDelete']);




Route::prefix('manager/inventory')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::get('/', [InventoryController::class, 'index']);
    Route::get('/hidden', [InventoryController::class, 'hidden']);
    Route::get('/low-stock', [InventoryController::class, 'lowStockItems']);
    Route::post('/', [InventoryController::class, 'store']);
    Route::get('/{id}', [InventoryController::class, 'show']);
    Route::post('/{id}', [InventoryController::class, 'update']);
    Route::delete('/{id}', [InventoryController::class, 'destroy']);
    Route::post('{id}/subtract', [InventoryController::class, 'subtractQuantity']);
    Route::patch('{id}/low-stock', [InventoryController::class, 'setLowStock']);

    Route::delete('/{id}', [InventoryController::class, 'softDelete']);
    Route::post('/{id}/restore', [InventoryController::class, 'restore']);
    Route::delete('/{id}/force', [InventoryController::class, 'forceDelete']);
});

Route::prefix('manager/bills')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::get('/', [App\Http\Controllers\Manager\BillController::class, 'index']);
    Route::get('/hidden', [App\Http\Controllers\Manager\BillController::class, 'hidden']);
    Route::get('/status/{status}', [App\Http\Controllers\Manager\BillController::class, 'filterByStatus']);
    Route::get('/{bill}', [App\Http\Controllers\Manager\BillController::class, 'show']);
    Route::post('/{bill}/discount', [App\Http\Controllers\Manager\BillController::class, 'applyDiscount']);
    Route::get('/table/{tableId}', [App\Http\Controllers\Manager\BillController::class, 'getByTable']);
    Route::delete('/{bill}', [BillController::class, 'softDelete']);
    Route::delete('/{bill}/force', [BillController::class, 'forceDelete']);
    Route::patch('/{id}/restore', [BillController::class, 'restore']);
});

Route::prefix('manager/staff')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::get('/', [App\Http\Controllers\Manager\StaffController::class, 'index']);
    Route::get('/hidden', [App\Http\Controllers\Manager\StaffController::class, 'hidden']);
    Route::get('/bonus', [App\Http\Controllers\Manager\StaffController::class, 'bonusindex']);
    Route::get('/{staff}', [App\Http\Controllers\Manager\StaffController::class, 'show']);
    Route::post('/', [App\Http\Controllers\Manager\StaffController::class, 'store']);
    Route::post('/{staff}', [App\Http\Controllers\Manager\StaffController::class, 'update']);
    Route::post('/{staff}/bonus', [App\Http\Controllers\Manager\StaffController::class, 'applyBonus']);
    Route::put('/bonus/{bonusHistory}', [App\Http\Controllers\Manager\StaffController::class, 'updateBonus']);
    Route::delete('/bonus/{bonusHistory}', [App\Http\Controllers\Manager\StaffController::class, 'deleteBonus']);

    Route::delete('/{id}', [StaffController::class, 'softDelete']);
    Route::post('/{id}/restore', [StaffController::class, 'restore']);
    Route::delete('/{id}/force', [StaffController::class, 'forceDelete']);
});

Route::prefix('manager/menu')->namespace('App\Http\Controllers\Manager')->group(function () {
    Route::get('/', [App\Http\Controllers\Manager\MenuController::class, 'index']);
    Route::get('/hidden', [App\Http\Controllers\Manager\MenuController::class, 'hidden']);
    Route::get('/{id}', [App\Http\Controllers\Manager\MenuController::class, 'show']);
    Route::post('/', [App\Http\Controllers\Manager\MenuController::class, 'store']);
    Route::post('/{id}', [App\Http\Controllers\Manager\MenuController::class, 'update']);

    Route::delete('{id}', [MenuController::class, 'softDelete']);
    Route::post('{id}/restore', [MenuController::class, 'restore']);
    Route::delete('{id}/force', [MenuController::class, 'forceDelete']);
});

Route::get('/reports/sales', [ReportController::class, 'salesReport']);
Route::get('/reports/kitchen-section', [ReportController::class, 'kitchenSectionReport']);
Route::get('/reports/popular-dishes', [ReportController::class, 'popularDishesReport']);
Route::get('/reports/bills', [ReportController::class, 'billReport']);
Route::get('/reports/tables', [ReportController::class, 'tableReport']);

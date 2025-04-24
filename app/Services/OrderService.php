<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrderWithItems($tableNumber, $items)
    {
        return DB::transaction(function () use ($tableNumber, $items) {
            // Find or create an open Bill
            $bill = Bill::firstOrCreate(
                ['table_number' => $tableNumber, 'status' => 'open'],
                ['total' => 0]
            );

            // Create a new Order under this Bill
            $order = $bill->orders()->create([
                'table_number' => $tableNumber,
            ]);

            $totalOrderPrice = 0;

            foreach ($items as $item) {

                $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                $price = $menuItem->price;

                $total = $price * $item['quantity'];
                $totalOrderPrice += $total;

                $order->items()->create([
                    'table_number' => $tableNumber,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price * $item['quantity'],
                    'status' => 'preparing',
                ]);
            }



            // Update order total and bill total
            $order->update(['total_price' => $totalOrderPrice]);
            $bill->update(['total' => $bill->total + $totalOrderPrice]);

            return $order->load('items');
        });
    }

    public function updateOrder($orderId, $items)
    {
        return DB::transaction(function () use ($orderId, $items) {
            $order = Order::findOrFail($orderId);
            $order->items()->delete(); // Remove existing items

            $totalOrderPrice = 0;

            foreach ($items as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                $price = $menuItem->price;
                $total = $price * $item['quantity'];
                $totalOrderPrice += $total;

                $order->items()->create([
                    'table_number' => $order->table_number,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price * $item['quantity'],
                    'status' => 'preparing',
                ]);
            }

            $order->update(['total_price' => $totalOrderPrice]);
            $order->bill->update(['total' => $order->bill->orders()->sum('total_price')]);

            return $order->load('items');
        });
    }
    public function cancelOrder($orderId, $reason = null)
    {
        return DB::transaction(function () use ($orderId, $reason) {
            $order = Order::findOrFail($orderId);

            // Mark order as canceled
            $order->is_canceled = true;
            $order->cancel_reason = $reason;
            $order->save();

            // Update all items of this order to canceled
            $order->items()->update(['status' => 'canceled']);

            // Update the bill total (exclude canceled orders)
            $order->bill->update([
                'total' => $order->bill->orders()->where('is_canceled', false)->sum('total_price')
            ]);

            return $order->load('items');
        });
    }

    public function closeBill($billId, $discount = 0)
    {
        $bill = Bill::findOrFail($billId);
        $finalTotal = $bill->total - $discount;
        $bill->update([
            'total' => max($finalTotal, 0),
            'status' => 'paid'
        ]);
        return $bill;
    }

    public function updateOrderItem($orderItemId, $newMenuItemId, $newQuantity)
    {
        return DB::transaction(function () use ($orderItemId, $newMenuItemId, $newQuantity) {
            $orderItem = OrderItem::findOrFail($orderItemId);
            $order = $orderItem->order;
            $bill = $order->bill;

            $menuItem = MenuItem::findOrFail($newMenuItemId);
            $newPrice = $menuItem->price;
            $newTotal = $newPrice * $newQuantity;

            // Update the item
            $orderItem->update([
                'menu_item_id' => $newMenuItemId,
                'quantity' => $newQuantity,
                'price' => $newPrice * $newQuantity,
            ]);

            // Recalculate order total
            $orderTotal = $order->items()->sum(DB::raw('price * quantity'));
            $order->update(['total_price' => $orderTotal]);

            // Recalculate bill total (excluding canceled orders)
            $billTotal = $bill->orders()->where('is_canceled', false)->sum('total_price');
            $bill->update(['total' => $billTotal]);

            return $orderItem->fresh();
        });
    }
}

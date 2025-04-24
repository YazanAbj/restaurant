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
            // Check if an open bill already exists
            $bill = Bill::where('table_number', $tableNumber)
                        ->where('status', 'open')
                        ->first();

            $totalOrderPrice = 0;

            if (!$bill) {
                // If no open bill, create one
                $bill = Bill::create([
                    'table_number' => $tableNumber,
                    'status' => 'open',
                    'total' => 0,
                    'final_price' => 0,
                ]);
            }

            // Create a new order under this bill
            $order = $bill->orders()->create([
                'table_number' => $tableNumber,
            ]);

            foreach ($items as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                $price = $menuItem->price;
                $total = $price * $item['quantity'];
                $totalOrderPrice += $total;

                $order->items()->create([
                    'table_number' => $tableNumber,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $total,
                    'status' => 'preparing',
                ]);
            }

            // Update order total and bill total
            $order->update(['total_price' => $totalOrderPrice]);
            
            $bill->total += $totalOrderPrice;
            $bill->final_price += $totalOrderPrice;

            // Set final_price only if this is the first order
            if ($bill->final_price == 0) {
                $bill->final_price = $bill->total;
            }

            $bill->save();

            return $order->load('items');
        });
    }

    public function updateOrder($orderId, $items)
    {
        return DB::transaction(function () use ($orderId, $items) {
            $order = Order::findOrFail($orderId);
            $order->items()->delete();

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
                    'price' => $total,
                    'status' => 'preparing',
                ]);
            }

            $order->update(['total_price' => $totalOrderPrice]);
            $order->bill->update([
                'total' => $order->bill->orders()->where('is_canceled', false)->sum('total_price')
            ]);

            return $order->load('items');
        });
    }

    public function cancelOrder($orderId, $reason = null)
    {
        return DB::transaction(function () use ($orderId, $reason) {
            $order = Order::findOrFail($orderId);

            $order->update([
                'is_canceled' => true,
                'cancel_reason' => $reason
            ]);

            $order->items()->update(['status' => 'canceled']);

            $order->bill->update([
                'total' => $order->bill->orders()->where('is_canceled', false)->sum('total_price')
            ]);

            return $order->load('items');
        });
    }



    public function cancelOrderItem($orderItemId)
    {
        return DB::transaction(function () use ($orderItemId) {
            $orderItem = OrderItem::findOrFail($orderItemId);
            $order = $orderItem->order;
            $bill = $order->bill;

            // Update item status
            $orderItem->update(['status' => 'canceled']);

            // Recalculate order total, excluding canceled items
            $orderTotal = $order->items()
                ->where('status', '!=', 'canceled')
                ->sum(DB::raw('price * quantity'));

            $order->update(['total_price' => $orderTotal]);

            // Recalculate bill total (excluding canceled orders)
            $billTotal = $bill->orders()->where('is_canceled', false)->sum('total_price');
            $bill->update(['total' => $billTotal]);

            return $orderItem->fresh();
        });
    }

    public function deleteOrderItem($orderItemId)
    {
        return DB::transaction(function () use ($orderItemId) {
            $orderItem = OrderItem::findOrFail($orderItemId);
            $order = $orderItem->order;
            $bill = $order->bill;

            $orderItem->delete();

            // Recalculate order total
            $orderTotal = $order->items()->sum(DB::raw('price * quantity'));
            $order->update(['total_price' => $orderTotal]);

            // Recalculate bill total (excluding canceled orders)
            $billTotal = $bill->orders()->where('is_canceled', false)->sum('total_price');
            $bill->update(['total' => $billTotal]);

            return true;
        });
    }

    public function closeBill($billId, $discount = 0)
    {
        return DB::transaction(function () use ($billId, $discount) {
            $bill = Bill::findOrFail($billId);

            // Final price is already set when the bill is created
            $discountedTotal = max($bill->total - $discount, 0);

            $bill->update([
                'total' => $discountedTotal,
                'status' => 'paid',
            ]);

            return $bill;
        });
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

            $orderItem->update([
                'menu_item_id' => $newMenuItemId,
                'quantity' => $newQuantity,
                'price' => $newTotal,
            ]);

            $orderTotal = $order->items()->sum(DB::raw('price'));
            $order->update(['total_price' => $orderTotal]);

            $billTotal = $bill->orders()->where('is_canceled', false)->sum('total_price');
            $bill->update(['total' => $billTotal]);

            return $orderItem->fresh();
        });
    }
}

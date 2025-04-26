<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\KitchenSection;
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

                // Determine kitchen section by menu item category
                $kitchenSection = KitchenSection::whereJsonContains('categories', $menuItem->category)->first();

                $order->items()->create([
                    'table_number' => $tableNumber,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price* $item['quantity'],
                    'status' => 'preparing',
                    'kitchen_section_id' => optional($kitchenSection)->id,
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


                $kitchenSection = KitchenSection::whereJsonContains('categories', $menuItem->category)->first();

                $order->items()->create([
                    'table_number' => $order->table_number,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price* $item['quantity'],
                    'status' => 'preparing',
                    'kitchen_section_id' => optional($kitchenSection)->id,
                ]);
            }

            $order->update(['total_price' => $totalOrderPrice]);
            $order->bill->update([
                'total' => $order->bill->orders()->where('is_canceled', false)->sum('total_price')
            ]);
            $order->bill->update([
                'final_price' => $order->bill->orders()->where('is_canceled', false)->sum('total_price')
            ]);
            

            return $order->load('items');
        });
    }


    public function cancelOrder($orderId, $reason = null)
    {
        return DB::transaction(function () use ($orderId, $reason) {
            $order = Order::findOrFail($orderId);

            if ($order->is_canceled) {
                return response()->json(['message' => 'You have already canceled this order.'], 409);
            }

            // Cancel the order
            $order->update([
                'is_canceled' => true,
                'cancel_reason' => $reason,
                'total_price' => 0, // Important: set order total to 0
            ]);

            // Cancel the order items
            $order->items()->update(['status' => 'canceled']);

            // Recalculate and update the bill total
            $order->bill->update([
                'total' => $order->bill->orders()->where('is_canceled', false)->sum('total_price')
            ]);
            $order->bill->update([
                'final_price' => $order->bill->orders()->where('is_canceled', false)->sum('total_price')
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
            $bill->update(['final_price' => $billTotal]);


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
            $bill->update(['final_price' => $billTotal]);

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


            $kitchenSection = KitchenSection::whereJsonContains('categories', $menuItem->category)->first();

            $orderItem->update([
                'menu_item_id' => $newMenuItemId,
                'quantity' => $newQuantity,
                'price' => $newTotal,
                'kitchen_section_id' => optional($kitchenSection)->id,
            ]);

            $orderTotal = $order->items()->sum(DB::raw('price'));
            $order->update(['total_price' => $orderTotal]);

            $billTotal = $bill->orders()->where('is_canceled', false)->sum('total_price');
            $bill->update(['total' => $billTotal]);
            $bill->update(['final_price' => $billTotal]);

            return $orderItem->fresh();
        });
    }
}

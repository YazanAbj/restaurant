<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\KitchenSection;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrderWithItems($tableNumber, $items, $user)
    {
        return DB::transaction(function () use ($tableNumber, $items, $user) {
            $table = Table::where('table_number', $tableNumber)->first();
            if ($table && $table->status !== 'occupied') {
                $table->update(['status' => 'occupied']);
            }

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

            $order = $bill->orders()->create([
                'table_number' => $tableNumber,
                'user_id' => $user->id,
            ]);

            foreach ($items as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                $price = $menuItem->price;
                $total = $price * $item['quantity'];
                $totalOrderPrice += $total;

                $kitchenSection = KitchenSection::whereJsonContains('categories', $menuItem->category)->first();

                $order->items()->create([
                    'table_number' => $tableNumber,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $total,
                    'status' => 'preparing',
                    'kitchen_section_id' => optional($kitchenSection)->id,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $order->update(['total_price' => $totalOrderPrice]);

            $bill->total += $totalOrderPrice;
            $bill->final_price += $totalOrderPrice;
            if ($bill->final_price == 0) {
                $bill->final_price = $bill->total;
            }
            $bill->save();

            // ✅ Load user relation for name
            return $order->load(['items.menuItem', 'user']);
        });
    }

    public function updateOrder($orderId, $items, $user)
    {
        return DB::transaction(function () use ($orderId, $items, $user) {
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
                    'price' => $total,
                    'status' => 'preparing',
                    'kitchen_section_id' => optional($kitchenSection)->id,
                ]);
            }

            $order->update([
                'total_price' => $totalOrderPrice,
                'user_id' => $user->id, // ✅ Update user_id to current waiter
            ]);

            $order->bill->update([
                'total' => $order->bill->orders()->where('is_canceled', false)->sum('total_price'),
                'final_price' => $order->bill->orders()->where('is_canceled', false)->sum('total_price'),
            ]);

            return $order->load(['items.menuItem', 'user']); // ✅ Include user in response
        });
    }

    public function cancelOrder($orderId, $reason = null)
    {
        return DB::transaction(function () use ($orderId, $reason) {
            $order = Order::find($orderId);

            if (!$order) {
                abort(404, 'Order not found.');
            }

            if ($order->is_canceled) {
                abort(409, 'Order already canceled.');
            }

            $order->update([
                'is_canceled' => true,
                'cancel_reason' => $reason,
                'total_price' => 0,
            ]);

            $order->items()->update(['status' => 'canceled']);

            $order->bill->update([
                'total' => $order->bill->orders()->where('is_canceled', false)->sum('total_price'),
                'final_price' => $order->bill->orders()->where('is_canceled', false)->sum('total_price'),
            ]);

            return $order->load('items');
        });
    }


    public function cancelOrderItem($orderItemId)
    {
        return DB::transaction(function () use ($orderItemId) {
            $orderItem = OrderItem::find($orderItemId);

            if (!$orderItem) {
                abort(404, 'Order item not found.');
            }

            if ($orderItem->status === 'canceled') {
                abort(409, 'Order item already canceled.');
            }

            $order = $orderItem->order;
            $bill = $order->bill;

            $orderItem->update(['status' => 'canceled']);

            $orderTotal = $order->items()->where('status', '!=', 'canceled')->sum(DB::raw('price * quantity'));
            $order->update(['total_price' => $orderTotal]);

            $billTotal = $bill->orders()->where('is_canceled', false)->sum('total_price');
            $bill->update(['total' => $billTotal, 'final_price' => $billTotal]);

            return $orderItem->fresh();
        });
    }

    public function deleteOrderItem($orderItemId)
    {
        return DB::transaction(function () use ($orderItemId) {
            $orderItem = OrderItem::find($orderItemId);

            if (!$orderItem) {
                abort(404, 'Order item not found.');
            }

            $order = $orderItem->order;
            $bill = $order->bill;

            $orderItem->delete();

            $orderTotal = $order->items()->sum(DB::raw('price * quantity'));
            $order->update(['total_price' => $orderTotal]);

            $billTotal = $bill->orders()->where('is_canceled', false)->sum('total_price');
            $bill->update(['total' => $billTotal, 'final_price' => $billTotal]);

            return true;
        });
    }

    public function closeBill($billId, $discount = 0)
    {
        return DB::transaction(function () use ($billId, $discount) {
            $bill = Bill::findOrFail($billId);

            $unservedOrders = $bill->orders()->where('has_been_served', false)->exists();

            if ($unservedOrders) {
                return [
                    'success' => false,
                    'message' => 'Cannot close bill: Some orders have not been fully served.'
                ];
            }

            $discountedTotal = max($bill->total - $discount, 0);

            $bill->update([
                'total' => $discountedTotal,
                'status' => 'paid',
            ]);

            $firstOrder = $bill->orders->first();
            if ($firstOrder && $firstOrder->table_number) {
                Table::where('table_number', $firstOrder->table_number)->update(['status' => 'free']);
            }

            return [
                'success' => true,
                'bill' => $bill
            ];
        });
    }


    public function updateOrderItem($orderItemId, $newMenuItemId, $newQuantity, $newNotes = null)
    {
        return DB::transaction(function () use ($orderItemId, $newMenuItemId, $newQuantity, $newNotes) {
            $orderItem = OrderItem::find($orderItemId);

            if (!$orderItem) {
                abort(404, 'Order item not found.');
            }

            if ($orderItem->status === 'canceled') {
                abort(409, 'Cannot update a canceled order item.');
            }

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
                'notes' => $newNotes,
            ]);

            $orderTotal = $order->items()->where('status', '!=', 'canceled')->sum(DB::raw('price * quantity'));
            $order->update(['total_price' => $orderTotal]);

            $billTotal = $bill->orders()->where('is_canceled', false)->sum('total_price');
            $bill->update(['total' => $billTotal, 'final_price' => $billTotal]);

            return $order->fresh(['items.menuItem', 'user']);
        });
    }
}

<?php

namespace App\Services;

use App\Models\Bill;
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
                'ordered_at' => now(),
            ]);

            $totalOrderPrice = 0;

            foreach ($items as $item) {
                $total = $item['price'] * $item['quantity'];
                $totalOrderPrice += $total;

                $order->items()->create([
                    'table_number' => $tableNumber,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'status' => 'preparing',
                ]);
            }


            // Update order total and bill total
            $order->update(['total_price' => $totalOrderPrice]);
            $bill->update(['total' => $bill->total + $totalOrderPrice]);

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
}

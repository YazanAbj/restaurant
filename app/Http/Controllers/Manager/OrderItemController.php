<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function store(Request $request, Order $order)
    {
        if ($order->has_been_served) {
            return response()->json(['message' => 'Cannot add items to a served order.'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
        ]);

        $subtotal = $request->price * $request->quantity;

        $item = $order->orderItems()->create([
            'name' => $request->name,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'subtotal' => $subtotal,
        ]);

        $order->increment('total_price', $subtotal);
        $order->bill->increment('total', $subtotal);

        return response()->json(['message' => 'Item added.', 'item' => $item]);
    }

    public function destroy(OrderItem $item)
    {
        $order = $item->order;

        if ($order->has_been_served) {
            return response()->json(['message' => 'Cannot remove items from a served order.'], 403);
        }

        $subtotal = $item->subtotal;
        $item->delete();

        $order->decrement('total_price', $subtotal);
        $order->bill->decrement('total', $subtotal);

        return response()->json(['message' => 'Item removed.']);
    }
}

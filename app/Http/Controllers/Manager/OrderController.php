<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items.menuItem', 'table']);

        if ($request->has('date')) {
            $query->whereDate('ordered_at', $request->date);
        }

        if ($request->has('hour')) {
            $query->whereTime('ordered_at', '>=', $request->hour . ':00:00')
                  ->whereTime('ordered_at', '<', ($request->hour + 1) . ':00:00');
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'table_id' => 'required|exists:tables,id',
            'ordered_at' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            
        ]);

        $total = 0;

        // Create the order first
        $order = Order::create([
            'customer_name' => $data['customer_name'],
            'table_id' => $data['table_id'],
            'ordered_at' => $data['ordered_at'],
            'total_price' => 0, // temporary
        ]);

        foreach ($data['items'] as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);
            $lineTotal = $menuItem->price * $item['quantity'];
            $total += $lineTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'price' => $menuItem->price, // Automatically from DB
            ]);
        }

        

        $order->total_price = max($total, 0);
        $order->save();

        return response()->json(['message' => 'Order created', 'order' => $order], 201);
    }

    public function show($id)
    {
        $order = Order::with(['items.menuItem', 'table'])->findOrFail($id);
        return response()->json($order);
    }

    public function update(Request $request, $id)
{
    $order = Order::findOrFail($id);

    // Check if the status is 'pending' before allowing updates
    if ($order->status !== 'pending') {
        return response()->json(['message' => 'Only pending orders can be updated.'], 400);
    }

    $data = $request->validate([
        'customer_name' => 'sometimes|string|max:255',
        'table_id' => 'sometimes|exists:tables,id',
        'ordered_at' => 'sometimes|date',
        'items' => 'sometimes|array|min:1',
        'items.*.menu_item_id' => 'required_with:items|exists:menu_items,id',
        'items.*.quantity' => 'required_with:items|integer|min:1',
    ]);

    // Update order details
    $order->update($data);

    // If there are items to update
    if (isset($data['items'])) {
        // Delete old items and recalculate total
        $order->items()->delete();
        $total = 0;

        foreach ($data['items'] as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);
            $lineTotal = $menuItem->price * $item['quantity'];
            $total += $lineTotal;

            // Add the new items to the order
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'price' => $menuItem->price,
            ]);
        }

        // Update the total price of the order
        $order->total_price = max($total, 0);
        $order->save();
    }

    return response()->json(['message' => 'Order updated', 'order' => $order]);
}


    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }

    public function applyDiscount(Request $request, $id)
{
    $order = Order::findOrFail($id);

    $validated = $request->validate([
        'type' => 'required|in:percent,fixed',
        'value' => 'required|numeric|min:0',
    ]);

    $total = $order->total_price;

    if ($validated['type'] === 'percent') {
        $total -= ($total * ($validated['value'] / 100));
    } else {
        $total -= $validated['value'];
    }

    $order->total_price = max(round($total, 2), 0);
    $order->save();

    return response()->json([
        'message' => 'Discount applied successfully',
        'total_price' => $order->total_price,
    ]);
}

}

<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Table;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = $this->orderService->createOrderWithItems(
            $validated['table_number'],
            $validated['items']
        );

        return response()->json(['order' => $order], 201);
    }

    public function closeBill($billId)
    {
        $bill = $this->orderService->closeBill($billId);
        return response()->json(['message' => 'Bill closed', 'bill' => $bill]);
    }

    public function update(Request $request, $orderId)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = $this->orderService->updateOrder($orderId, $validated['items']);
        return response()->json(['order' => $order]);
    }
    public function updateOrderItem(Request $request, $orderItemId)
    {
        $validated = $request->validate([
            'menu_item_id' => 'required|integer|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $orderItem = $this->orderService->updateOrderItem(
            $orderItemId,
            $validated['menu_item_id'],
            $validated['quantity']
        );

        return response()->json(['order_item' => $orderItem]);
    }


    public function index()
    {
        $orders = Order::with('items')->latest()->get();
        return response()->json(['orders' => $orders]);
    }

    public function show($orderId)
    {
        $order = Order::with('items')->findOrFail($orderId);
        return response()->json(['order' => $order]);
    }

    public function cancel(Request $request, $orderId)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $order = $this->orderService->cancelOrder($orderId, $validated['reason'] ?? null);
        return response()->json(['message' => 'Order canceled', 'order' => $order]);
    }
    public function destroy($orderId)
    {
        $order = Order::findOrFail($orderId);
        $order->delete();
        return response()->json(['message' => 'Order deleted']);
    }

    public function cancelOrderItem($orderItemId)
    {
        $orderItem = $this->orderService->cancelOrderItem($orderItemId);
        return response()->json(['message' => 'Order item canceled', 'order_item' => $orderItem]);
    }

    public function destroyOrderItem($orderItemId)
    {
        $this->orderService->deleteOrderItem($orderItemId);
        return response()->json(['message' => 'Order item deleted successfully']);
    }
}

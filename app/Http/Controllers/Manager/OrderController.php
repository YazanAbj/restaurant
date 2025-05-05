<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $order = $this->orderService->createOrderWithItems(
            $validated['table_number'],
            $validated['items'],
            $user
        );

        return response()->json([
            'order' => $order,
            'user' => $user
        ], 201);
    }

    public function closeBill($billId)
    {
        $result = $this->orderService->closeBill($billId);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json([
            'message' => 'Bill closed successfully.',
            'bill' => $result['bill']
        ]);
    }

    public function update(Request $request, $orderId)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = auth()->user();

        $order = $this->orderService->updateOrder($orderId, $validated['items'], $user);

        return response()->json(['order' => $order]);
    }


    public function updateOrderItem(Request $request, $orderItemId)
    {
        $validated = $request->validate([
            'menu_item_id' => 'required|integer|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = $this->orderService->updateOrderItem(
            $orderItemId,
            $validated['menu_item_id'],
            $validated['quantity'],
            $validated['notes'] ?? null
        );

        return response()->json(['order' => $order]);
    }



    public function index()
    {
        $orders = Order::with(['items.menuItem', 'user'])->latest()->get();
        return response()->json(['orders' => $orders]);
    }

    public function show($orderId)
    {
        $order = Order::with(['items.menuItem', 'user'])->findOrFail($orderId);
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
        $order = Order::with(['items.menuItem', 'user'])->find($orderId);

        if (!$order) {
            abort(404, 'Order not found.');
        }

        if ($order->is_canceled) {
            abort(409, 'Cannot delete a canceled order.');
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted',
            'order' => $order
        ]);
    }

    public function cancelOrderItem($orderItemId)
    {
        $orderItem = $this->orderService->cancelOrderItem($orderItemId);
        return response()->json(['message' => 'Order item canceled', 'order_item' => $orderItem]);
    }

    public function destroyOrderItem($orderItemId)
    {
        $orderItem = Order::find($orderItemId);

        if (!$orderItem) {
            abort(404, 'Order not found.');
        }

        if ($orderItem->is_canceled) {
            abort(409, 'Cannot delete a canceled order.');
        }

        $this->orderService->deleteOrderItem($orderItemId);
        return response()->json(['message' => 'Order item deleted successfully']);
    }
}

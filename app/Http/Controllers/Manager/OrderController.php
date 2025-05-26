<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Order;
use App\Models\OrderItem;
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

    public function index()
    {
        $orders = Order::with(['items.menuItem', 'user'])->latest()->get();
        return response()->json(['orders' => $orders]);
    }

    public function getItemsByStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,preparing,finished,canceled',
        ]);

        $items = $this->orderService->getOrderItemsByStatus($validated['status']);

        return response()->json(['items' => $items]);
    }

    public function getOrdersByTableNumber($tableNumber)
    {
        $table = Table::where('table_number', $tableNumber)->first();

        if (!$table) {
            return response()->json(['message' => 'Table not found.'], 404);
        }

        if ($table->status === 'free') {
            return response()->json(['message' => 'Table is free, no active orders.']);
        }

        $bill = Bill::where('table_number', $table->table_number)->where('status', 'open')->first();

        if (!$bill) {
            return response()->json(['message' => 'No open bill found for this table.']);
        }

        $orders = $bill->orders()->with('items.menuItem')->get();

        return response()->json([
            'table' => $table->table_number,
            'bill_id' => $bill->id,
            'orders' => $orders,
        ]);
    }

    public function filterOrdersByBillStatus(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:open,paid',
        ]);

        try {
            $orders = $this->orderService->getOrdersByBillStatus($request->query('status'));

            return response()->json([
                'status' => $request->query('status'),
                'orders' => $orders,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }



    public function hidden()
    {
        $trashedOrders = Order::onlyTrashed()
            ->with([
                'items' => function ($q) {
                    $q->withTrashed();
                },
                'items.menuItem' => function ($q) {
                    $q->withTrashed();
                },
            ])
            ->get();

        return response()->json($trashedOrders);
    }



    public function show($orderId)
    {
        $order = Order::with(['items.menuItem', 'user'])->findOrFail($orderId);
        return response()->json(['order' => $order]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|integer|exists:tables,table_number',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $table = Table::where('table_number', $validated['table_number'])->whereNull('deleted_at')->first();

        if (!$table) {
            return response()->json(['error' => 'Table not found or has been deleted'], 404);
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


    public function setPreparing($orderItemId)
    {
        $orderItem = OrderItem::findOrFail($orderItemId);

        if ($orderItem->status !== 'pending') {
            return response()->json([
                'message' => 'Order item is not pending, so it cannot be set to preparing.',
                'order_item' => $orderItem
            ], 409);
        }

        $orderItem->status = 'preparing';
        $orderItem->save();

        return response()->json([
            'message' => 'Order item status updated to preparing.',
            'order_item' => $orderItem
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
            'force' => 'sometimes|boolean',
        ]);

        $order = $this->orderService->updateOrderItem(
            $orderItemId,
            $validated['menu_item_id'],
            $validated['quantity'],
            $validated['notes'] ?? null,
            $validated['force'] ?? false
        );

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

    public function cancelOrderItem(Request $request, $orderItemId)
    {
        $force = $request->boolean('force', false);
        $orderItem = $this->orderService->cancelOrderItem($orderItemId, $force);
        return response()->json([
            'message' => $force && $orderItem->status === 'canceled'
                ? 'Order item force-canceled while preparing'
                : 'Order item canceled',
            'order_item' => $orderItem
        ]);
    }




    public function softDelete($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->bill && $order->bill->status !== 'paid') {
            return response()->json(['message' => 'Cannot delete order: related bill is not paid.'], 403);
        }

        $order->delete();
        return response()->json(['message' => 'Order soft-deleted successfully.']);
    }

    public function forceDelete($id)
    {
        $order = Order::withTrashed()->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->bill && $order->bill->status !== 'paid') {
            return response()->json(['message' => 'Cannot force delete order: related bill is not paid.'], 403);
        }

        $order->forceDelete();
        return response()->json(['message' => 'Order permanently deleted.']);
    }


    // Restore soft-deleted order
    public function restoreOrder($id)
    {
        $order = Order::withTrashed()->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if (!$order->trashed()) {
            return response()->json(['message' => 'Order is not soft-deleted.'], 400);
        }

        $order->restore();
        return response()->json(['message' => 'Order restored successfully.']);
    }


    //order Items
    public function softDeleteOrderItem($id)
    {
        $item = OrderItem::find($id);

        if (!$item) {
            return response()->json(['message' => 'Order item not found.'], 404);
        }

        if ($item->order && $item->order->bill && $item->order->bill->status !== 'paid') {
            return response()->json(['message' => 'Cannot delete order item: related bill is not paid.'], 403);
        }

        $item->delete();
        return response()->json(['message' => 'Order item soft-deleted successfully.']);
    }

    public function forceDeleteOrderItem($id)
    {
        $item = OrderItem::withTrashed()->find($id);

        if (!$item) {
            return response()->json(['message' => 'Order item not found.'], 404);
        }

        if ($item->order && $item->order->bill && $item->order->bill->status !== 'paid') {
            return response()->json(['message' => 'Cannot force delete order item: related bill is not paid.'], 403);
        }

        $item->forceDelete();
        return response()->json(['message' => 'Order item permanently deleted.']);
    }

    public function restoreOrderItem($id)
    {
        $item = OrderItem::withTrashed()->find($id);

        if (!$item) {
            return response()->json(['message' => 'Order item not found.'], 404);
        }

        if (!$item->trashed()) {
            return response()->json(['message' => 'Order item is not soft-deleted.'], 400);
        }

        $item->restore();
        return response()->json(['message' => 'Order item restored successfully.']);
    }
}

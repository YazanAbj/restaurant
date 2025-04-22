    <?php

    namespace App\Http\Controllers;


    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use App\Models\MenuItem;
    use App\Models\OrderItem;
    use App\Models\Table;
    use App\Models\Order;

    class OrderController extends Controller
    {
        // Start a new order for a table
        public function startOrder(Request $request)
        {
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'table_number' => 'required|exists:tables,id',
            ]);

            $table = Table::findOrFail($validated['table_number']);

            $order = Order::create([
                'customer_name' => $validated['customer_name'],
                'table_number' => $table->id,
                'status' => 'pending',
                'total_price' => 0,
            ]);

            return response()->json(['order' => $order], 201);
        }

        // Add item(s) to an existing order
        public function addItem(Request $request, $orderId)
        {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.menu_item_id' => 'required|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            $order = Order::findOrFail($orderId);

            if ($order->status !== 'pending') {
                return response()->json(['message' => 'Cannot modify order after it is sent'], 400);
            }

            $total = $order->total_price;

            DB::transaction(function () use ($validated, $order, &$total) {
                foreach ($validated['items'] as $item) {
                    $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                    $subtotal = $menuItem->price * $item['quantity'];
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $menuItem->id,
                        'quantity' => $item['quantity'],
                        'price' => $subtotal,
                    ]);
                    $total += $subtotal;
                }

                $order->update(['total_price' => $total]);
            });

            return response()->json(['message' => 'Items added successfully'], 200);
        }

        // Finalize order - send to chef
        public function sendToChef($orderId)
        {
            $order = Order::with('items')->findOrFail($orderId);

            if ($order->items->isEmpty()) {
                return response()->json(['message' => 'Order has no items'], 400);
            }

            if ($order->status !== 'pending') {
                return response()->json(['message' => 'Order already sent'], 400);
            }

            $order->update(['status' => 'sent_to_chef']);

            return response()->json(['message' => 'Order sent to chef successfully'], 200);
        }

        // Optional: list orders by status for kitchen
        public function kitchenOrders()
        {
            $orders = Order::with('items.menuItem')
                ->where('status', 'sent_to_chef')
                ->orderBy('ordered_at', 'asc')
                ->get();

            return response()->json(['orders' => $orders]);
        }
    }

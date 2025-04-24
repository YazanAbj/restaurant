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
            'items.*.price' => 'required|numeric|min:0',
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
}

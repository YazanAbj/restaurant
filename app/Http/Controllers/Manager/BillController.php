<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function show($id)
    {
        $bill = Bill::with(['orders.items.menuItem', 'orders.table'])->findOrFail($id);
        return response()->json($bill);
    }

    public function applyDiscount(Request $request, $id)
    {
        $bill = Bill::with('orders')->findOrFail($id);

        $data = $request->validate([
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
        ]);

        $originalTotal = $bill->orders->sum('total_price');
        $newTotal = $originalTotal;

        if ($data['type'] === 'percent') {
            $newTotal -= $originalTotal * ($data['value'] / 100);
        } else {
            $newTotal -= $data['value'];
        }

        $bill->total_amount = max(round($newTotal, 2), 0);
        $bill->save();

        return response()->json([
            'message' => 'Discount applied to bill successfully.',
            'bill' => $bill,
        ]);
    }
}

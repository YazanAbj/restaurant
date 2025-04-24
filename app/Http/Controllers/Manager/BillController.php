<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

use App\Models\Bill;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function showOpenBill($tableNumber)
    {
        $bill = Bill::where('table_number', $tableNumber)
            ->where('status', 'open')
            ->with('orders.orderItems')
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'No open bill found.'], 404);
        }

        return response()->json($bill);
    }

    public function show(Bill $bill)
    {
        $bill->load('orders.orderItems');
        return response()->json($bill);
    }

    public function closeBill(Bill $bill)
    {
        $bill->status = 'paid';
        $bill->save();

        return response()->json(['message' => 'Bill closed successfully.']);
    }

    public function applyDiscount(Request $request, Bill $bill)
    {
        $request->validate([
            'discount' => 'required|numeric|min:0|max:' . $bill->total,
        ]);

        $bill->total -= $request->discount;
        $bill->save();

        return response()->json(['message' => 'Discount applied.', 'total' => $bill->total]);
    }
}

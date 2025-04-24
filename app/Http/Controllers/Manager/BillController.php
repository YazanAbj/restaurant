<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;

class BillController extends Controller
{
    // ✅ Show all bills
    public function index()
    {
        return response()->json(Bill::with('orders.Items')->get());
    }

    // ✅ Show all open or closed bills
    public function filterByStatus($status)
    {
        if (!in_array($status, ['open', 'paid'])) {
            return response()->json(['message' => 'Invalid status.'], 400);
        }

        $bills = Bill::where('status', $status)->with('orders.Items')->get();
        return response()->json($bills);
    }

    // ✅ Show a single bill
    public function show(Bill $bill)
    {
        $bill->load('orders.Items');
        return response()->json($bill);
    }

    // ✅ Apply discount
    public function applyDiscount(Request $request, Bill $bill)
    {
        $request->validate([
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
        ]);

        $bill->discount_type = $request->discount_type;
        $bill->discount_value = $request->discount_value;
        $bill->applyDiscount()->save();

        return response()->json([
            'message' => 'Discount applied successfully.',
            'final_price' => $bill->final_price,
            'discount_amount' => $bill->discount_amount,
        ]);
    }

    // ✅ Delete a bill
    public function destroy(Bill $bill)
    {
        $bill->delete();
        return response()->json(['message' => 'Bill deleted successfully.']);
    }
}

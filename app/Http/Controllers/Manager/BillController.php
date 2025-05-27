<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;

class BillController extends Controller
{

    public function index(Request $request)
    {
        $query = Bill::with('orders.items');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }


    public function filterByStatus($status)
    {
        if (!in_array($status, ['open', 'paid'])) {
            return response()->json(['message' => 'Invalid status.'], 400);
        }

        $bills = Bill::where('status', $status)->with('orders.Items')->get();
        return response()->json($bills);
    }

    public function hidden()
    {
        $trashedItems = Bill::onlyTrashed()->get();
        return response()->json($trashedItems);
    }

    public function getByTable($tableId)
    {
        $bill = Bill::where('table_id', $tableId)
            ->where('status', 'open')
            ->with(['orders.items.menuItem', 'orders.user'])
            ->first();

        return response()->json(['bill' => $bill]);
    }

    public function show(Bill $bill)
    {
        $bill->load('orders.Items');
        return response()->json($bill);
    }


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

    public function softDelete(Bill $bill)
    {
        if ($bill->status !== 'paid') {
            return response()->json(['message' => 'Only paid bills can be deleted.'], 403);
        }

        $bill->delete();

        return response()->json(['message' => 'Bill soft-deleted successfully.']);
    }

    public function forceDelete($id)
    {
        $bill = Bill::withTrashed()->findOrFail($id);

        if ($bill->status !== 'paid') {
            return response()->json(['message' => 'Only paid bills can be permanently deleted.'], 403);
        }

        $bill->forceDelete();

        return response()->json(['message' => 'Bill permanently deleted.']);
    }

    public function restore($id)
    {
        $bill = Bill::withTrashed()->find($id);

        if (!$bill) {
            return response()->json(['message' => 'Bill not found.'], 404);
        }

        if (!$bill->trashed()) {
            return response()->json(['message' => 'Bill is not soft-deleted.'], 400);
        }

        $bill->restore();

        return response()->json(['message' => 'Bill restored successfully.']);
    }
}

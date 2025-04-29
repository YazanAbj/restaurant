<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItem;

class InventoryController extends Controller
{
    // Show inventory with filters
    public function index(Request $request)
    {
        $query = InventoryItem::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('sort_by') && in_array($request->sort_by, ['expiry_date', 'received_date'])) {
            $direction = $request->get('direction', 'asc');
            $query->orderBy($request->sort_by, $direction);
        }

        return response()->json($query->get());
    }

    // Store a new inventory item
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'quantity' => 'required|integer',
            'unit' => 'required|string',
            'price_per_unit' => 'required|numeric',
            'supplier_name' => 'required|string',
            'received_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'low_stock_threshold' => 'required|integer',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        }

        // Determine low_stock
        $data['low_stock'] = $data['quantity'] <= $data['low_stock_threshold'];

        $item = InventoryItem::create($data);

        return response()->json($item, 201);
    }

    // Show a single item
    public function show($id)
    {
        $item = InventoryItem::findOrFail($id);
        return response()->json($item);
    }

    // Update an existing item
    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'quantity' => 'sometimes|integer',
            'unit' => 'sometimes|string',
            'price_per_unit' => 'sometimes|numeric',
            'supplier_name' => 'sometimes|string',
            'received_date' => 'sometimes|date',
            'expiry_date' => 'nullable|date',
            'low_stock_threshold' => 'sometimes|integer',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $item->update($data);

        // Re-check low_stock if quantity or threshold is updated
        $quantity = $data['quantity'] ?? $item->quantity;
        $threshold = $data['low_stock_threshold'] ?? $item->low_stock_threshold;
        $item->low_stock = $quantity <= $threshold;
        $item->save();

        return response()->json($item);
    }

    // Delete an item
    public function destroy($id)
    {
        InventoryItem::findOrFail($id)->delete();
        return response()->json(['message' => 'Item deleted']);
    }

    // Get only low stock items
    public function lowStockItems()
    {
        $items = InventoryItem::where('low_stock', true)->get();
        return response()->json($items);
    }

    // Subtract a quantity from the inventory item
public function subtractQuantity(Request $request, $id)
{
    $item = InventoryItem::findOrFail($id);

    $data = $request->validate([
        'amount' => 'required|integer|min:1',
    ]);

    // Prevent negative quantity
    if ($item->quantity < $data['amount']) {
        return response()->json(['error' => 'Not enough stock to subtract that amount.'], 400);
    }

    // Subtract quantity
    $item->quantity -= $data['amount'];

    // Recalculate low stock
    $item->low_stock = $item->quantity <= $item->low_stock_threshold;

    $item->save();

    return response()->json([
        'message' => 'Quantity subtracted successfully',
        'item' => $item
    ]);
}

}

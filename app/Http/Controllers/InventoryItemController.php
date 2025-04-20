<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index()
    {
        return response()->json(InventoryItem::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'quantity'       => 'required|integer|min:0',
            'unit'           => 'required|string|max:50',
            'price_per_unit' => 'required|numeric|min:0',
            'supplier_name'  => 'required|string|max:255',
            'received_date'  => 'required|date',
            'expiry_date'    => 'nullable|date|after_or_equal:received_date',
        ]);

        $item = InventoryItem::create($data);

        return response()->json($item, 201);
    }

    public function show(string $id)
    {
        $item = InventoryItem::find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        return response()->json($item);
    }

    public function update(Request $request, string $id)
    {
        $item = InventoryItem::find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'description'    => 'nullable|string',
            'quantity'       => 'sometimes|integer|min:0',
            'unit'           => 'sometimes|string|max:50',
            'price_per_unit' => 'sometimes|numeric|min:0',
            'supplier_name'  => 'sometimes|string|max:255',
            'received_date'  => 'sometimes|date',
            'expiry_date'    => 'nullable|date|after_or_equal:received_date',
        ]);

        $item->update($data);
        return response()->json($item);
    }

    public function destroy(string $id)
    {
        $item = InventoryItem::find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }

    
}

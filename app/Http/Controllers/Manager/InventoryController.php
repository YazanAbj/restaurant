<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\Category;

class InventoryController extends Controller
{
    // Show inventory with filters
    public function index(Request $request)
    {
        $query = InventoryItem::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Sort by expiry or received date
        if ($request->has('sort_by') && in_array($request->sort_by, ['expiry_date', 'received_date'])) {
            $direction = $request->get('direction', 'asc');
            $query->orderBy($request->sort_by, $direction);
        }

        return response()->json($query->get());
    }

    // Store a new inventory item
    public function store(Request $request)
{
    // Validate input data
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
        'low_stock' => 'boolean',
        'photo' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240', // Validate file
    ]);

    // If there's a photo file, handle the upload
    if ($request->hasFile('photo')) {
        // Store the photo in the 'public/photos' directory and get the file path
        $photoPath = $request->file('photo')->store('photos', 'public');

        // Add the photo path to the data
        $data['photo'] = $photoPath;
    }

    // Create the inventory item and store it in the database
    $item = InventoryItem::create($data);

    // Return the newly created item as a response
    return response()->json($item, 201);
}


    // Show single item
    public function show($id)
    {
        $item = InventoryItem::findOrFail($id);
        return response()->json($item);
    }

    // Update inventory item (can set low stock here too)
    public function update(Request $request, $id)
    {
        // Find the inventory item by ID
        $item = InventoryItem::findOrFail($id);
    
        // Validate the incoming data
        $data = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string', // Just a string, no existence check needed
            'quantity' => 'sometimes|integer',
            'unit' => 'sometimes|string',
            'price_per_unit' => 'sometimes|numeric',
            'supplier_name' => 'sometimes|string',
            'received_date' => 'sometimes|date',
            'expiry_date' => 'nullable|date',
            'low_stock' => 'sometimes|boolean',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240', // Validate the file
        ]);
    

        
        // Check if a new photo file was uploaded
        if ($request->hasFile('photo')) {
            // Store the new photo in the 'public/photos' directory
            $photoPath = $request->file('photo')->store('photos', 'public');
    
            // Add the new photo path to the data
            $data['photo'] = $photoPath;
        }
    
        // Update the inventory item with the validated data
        $item->update($data);
    
        // Return the updated item as a response
        return response()->json($item);
    }
    

    // Delete inventory item
    public function destroy($id)
    {
        InventoryItem::findOrFail($id)->delete();
        return response()->json(['message' => 'Item deleted']);
    }

    // Optional: Set low stock separately
    public function setLowStock($id, Request $request)
    {
        $item = InventoryItem::findOrFail($id);
        $request->validate([
            'low_stock' => 'required|boolean',
        ]);

        $item->low_stock = $request->low_stock;
        $item->save();

        return response()->json(['message' => 'Low stock updated']);
    }
}

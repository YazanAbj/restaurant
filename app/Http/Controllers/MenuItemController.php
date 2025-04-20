<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\InventoryItem;
use App\Models\MenuItemRequirement;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(MenuItem::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming data
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric',
            'category'       => 'required|string',
            'image_path'     => 'nullable|string',
            'can_be_served'  => 'required|boolean',
            'requirements'   => 'required|array',
            'requirements.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'requirements.*.quantity' => 'required|integer|min:1',
        ]);

        // Create the menu item
        $menuItem = MenuItem::create([
            'name'           => $data['name'],
            'description'    => $data['description'],
            'price'          => $data['price'],
            'category'       => $data['category'],
            'image_path'     => $data['image_path'],
            'can_be_served'  => $data['can_be_served'],
        ]);

        // Now link the menu item with the inventory items and quantities
        foreach ($data['requirements'] as $requirement) {
            $menuItem->requirements()->create([
                'inventory_item_id' => $requirement['inventory_item_id'],
                'quantity' => $requirement['quantity'],
            ]);
        }

        return response()->json($menuItem, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $menuItem = MenuItem::with('requirements.inventoryItem')->find($id);

        if (!$menuItem) {
            return response()->json(['message' => 'Menu Item not found'], 404);
        }

        return response()->json($menuItem);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $menuItem = MenuItem::find($id);
        if (!$menuItem) {
            return response()->json(['message' => 'Menu Item not found'], 404);
        }
    
        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'sometimes|numeric',
            'category'       => 'sometimes|string',
            'image_path'     => 'nullable|string',
            'can_be_served'  => 'sometimes|boolean',
            'requirements'   => 'sometimes|array',
            'requirements.*.inventory_item_id' => 'required_with:requirements|exists:inventory_items,id',
            'requirements.*.quantity' => 'required_with:requirements|integer|min:1',
        ]);
    
        // Update menu item fields
        $menuItem->update($data);
    
        // If requirements are provided, update them
        if (isset($data['requirements'])) {
            // Delete old requirements
            $menuItem->requirements()->delete();
    
            // Create new ones
            foreach ($data['requirements'] as $requirement) {
                $menuItem->requirements()->create([
                    'inventory_item_id' => $requirement['inventory_item_id'],
                    'quantity' => $requirement['quantity'],
                ]);
            }
        }
    
        return response()->json($menuItem->load('requirements'));
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $menuItem = MenuItem::find($id);
        if (!$menuItem) {
            return response()->json(['message' => 'Menu Item not found'], 404);
        }

        // Delete the menu item along with its requirements
        $menuItem->requirements()->delete();
        $menuItem->delete();

        return response()->json(['message' => 'Menu Item deleted successfully']);
    }
}

<?php


namespace App\Http\Controllers\Manager;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\MenuItemRequirement;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    // Show all menu items with optional filters
    public function index(Request $request)
    {
        $query = MenuItem::query();

        // Optional filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->get());
    }

    // Store a new menu item with photo and inventory requirements
    public function store(Request $request)
    {
        // Validate incoming request data
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category' => 'required|string',
            'availability_status' => 'required|boolean',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240', // Validate image file
            'inventory_items' => 'required|array', // Inventory items
            'inventory_items.*.id' => 'required|exists:inventory_items,id', // Ensure inventory items exist
            'inventory_items.*.quantity' => 'required|integer|min:1', // Ensure quantity is provided
        ]);

        // Handle image upload if provided
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu_items', 'public');
            $data['image_path'] = $imagePath;
        }

        // Create the menu item
        $menuItem = MenuItem::create($data);

        // Attach inventory items and quantities to the menu item
        foreach ($data['inventory_items'] as $item) {
            MenuItemRequirement::create([
                'menu_item_id' => $menuItem->id,
                'inventory_item_id' => $item['id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return response()->json($menuItem, 201);
    }

    // Show a single menu item with its requirements
    public function show($id)
    {
        $menuItem = MenuItem::with('requirements.inventoryItem')->findOrFail($id);
        return response()->json($menuItem);
    }

    // Update an existing menu item
    public function update(Request $request, $id)
    {
        // Find the menu item by ID
        $menuItem = MenuItem::findOrFail($id);

        // Validate the incoming data
        $data = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'category' => 'sometimes|string',
            'availability_status' => 'sometimes|boolean',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240', // Validate image file
            'inventory_items' => 'nullable|array', // Optional inventory items
            'inventory_items.*.id' => 'required|exists:inventory_items,id', // Ensure inventory items exist
            'inventory_items.*.quantity' => 'required|integer|min:1', // Ensure quantity is provided
        ]);

        // Handle the image upload if a new image is provided
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($menuItem->image_path && Storage::disk('public')->exists($menuItem->image_path)) {
                Storage::disk('public')->delete($menuItem->image_path);
            }

            // Store the new image and update the path
            $imagePath = $request->file('image')->store('menu_items', 'public');
            $data['image_path'] = $imagePath;
        }

        // Update the menu item with the new data
        $menuItem->update($data);

        // Update inventory item requirements if provided
        if (isset($data['inventory_items'])) {
            // Remove existing requirements
            $menuItem->requirements()->delete();

            // Add new inventory item requirements
            foreach ($data['inventory_items'] as $item) {
                MenuItemRequirement::create([
                    'menu_item_id' => $menuItem->id,
                    'inventory_item_id' => $item['id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        }

        return response()->json($menuItem);
    }

    // Delete a menu item along with its requirements and photo
    public function destroy($id)
    {
        $menuItem = MenuItem::findOrFail($id);

        // Delete the image if it exists
        if ($menuItem->image_path && Storage::disk('public')->exists($menuItem->image_path)) {
            Storage::disk('public')->delete($menuItem->image_path);
        }

        // Delete the menu item and its associated requirements
        $menuItem->requirements()->delete();
        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);
    }
}

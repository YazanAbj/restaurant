<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\KitchenSection;
use Illuminate\Http\Request;
use App\Models\OrderItem;

class KitchenSectionController extends Controller
{

    public function queue($id)
    {
        $pendingItems = OrderItem::with('menuItem', 'order')
            ->where('kitchen_section_id', $id)
            ->where('status', 'preparing')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'pending_items' => $pendingItems
        ]);
    }

    public function markItemReady(Request $request, $id)
    {
        // $kitchenSection = $request->user()->kitchenSection ?? null; // assuming kitchen staff are authenticated and linked

        // if (!$kitchenSection) {
        //     return response()->json(['error' => 'Unauthorized or missing kitchen section'], 403);
        // }

        $orderItem = OrderItem::findOrFail($id);

        // // Check if the item belongs to this kitchen section
        // if ($orderItem->kitchen_section_id !== $kitchenSection->id) {
        //     return response()->json(['error' => 'This item does not belong to your kitchen section'], 403);
        // }

        if ($orderItem->status !== 'preparing') {
            return response()->json(['error' => 'Item is not in a preparable state'], 400);
        }

        $orderItem->update(['status' => 'ready']);

        return response()->json([
            'message' => 'Order item marked as ready',
            'order_item' => $orderItem
        ]);
    }

    public function readyItems($id)
    {
        $section = KitchenSection::findOrFail($id);

        $readyItems = OrderItem::where('kitchen_section_id', $section->id)
            ->where('status', 'finished')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'kitchen_section' => $section->name,
            'ready_items' => $readyItems
        ]);
    }



    public function itemsByStatus(Request $request, $id)
    {
        $section = KitchenSection::findOrFail($id);
        $status = $request->query('status');

        $query = OrderItem::where('kitchen_section_id', $id);

        if ($status) {
            $query->where('status', $status);
            $items = $query->orderBy('created_at')->get();

            return response()->json([
                'section' => $section->name,
                'filtered_status' => $status,
                'items' => $items,
            ]);
        }

        $preparing = (clone $query)->where('status', 'preparing')->orderBy('created_at')->get();
        $ready = (clone $query)->where('status', 'finieshed')->orderBy('created_at')->get();

        return response()->json([
            'section' => $section->name,
            'preparing' => $preparing,
            'finished' => $ready,
        ]);
    }
}

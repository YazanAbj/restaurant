<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class InventoryController extends Controller
{
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


    public function lowStockItems()
    {
        $items = InventoryItem::where('low_stock', true)->get();
        return response()->json($items);
    }



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

        $data['low_stock'] = $data['quantity'] <= $data['low_stock_threshold'];

        $item = InventoryItem::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = InventoryItem::findOrFail($id);
        return response()->json($item);
    }

   

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

    $quantity = $data['quantity'] ?? $item->quantity;
    $threshold = $data['low_stock_threshold'] ?? $item->low_stock_threshold;
    $item->low_stock = $quantity <= $threshold;
    $item->save();

    if ($item->low_stock) {
        $title = __('messages.low_stock_title'); 
        $body  = __('messages.low_stock_body', ['item' => $item->name]); 

        $firebase = (new Factory)
            ->withServiceAccount(public_path('restaurent-67df3-firebase-adminsdk-fbsvc-c42e5f0548.json'));

        $messaging = $firebase->createMessaging();

        foreach (
            User::whereIn('user_role', ['owner', 'manager','inventory_manager'])
                ->whereNotNull('fcm_token')
                ->get() as $user
        ) {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($title, $body));

            try {
                $messaging->send($message);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                \Log::error("FCM error for user {$user->id}: " . $e->getMessage());
            }
        }
    }

    return response()->json([$item]);
}


    public function subtractQuantity(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $data = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        if ($item->quantity < $data['amount']) {
            return response()->json(['error' => 'Not enough stock to subtract that amount.'], 400);
        }

        $item->quantity -= $data['amount'];

        $item->low_stock = $item->quantity <= $item->low_stock_threshold;

        $item->save();

       if ($item->low_stock) {
        $title = __('messages.low_stock_title');
        $body  = __('messages.low_stock_body', ['item' => $item->name]); 
        $firebase = (new Factory)
            ->withServiceAccount(public_path('restaurent-67df3-firebase-adminsdk-fbsvc-c42e5f0548.json'));

        $messaging = $firebase->createMessaging();

        foreach (
            User::whereIn('user_role', ['owner', 'manager','inventory_manager'])
                ->whereNotNull('fcm_token')
                ->get() as $user
        ) {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($title, $body));

            try {
                $messaging->send($message);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                \Log::error("FCM error for user {$user->id}: " . $e->getMessage());
            }
        }
    }
        return response()->json([
            'message' => 'Quantity subtracted successfully',
            'item' => $item
        ]);
    }




    public function setLowStock($id, Request $request)
    {
        $item = InventoryItem::findOrFail($id);
        $request->validate([
            'low_stock' => 'required|boolean',
        ]);
        $item->low_stock = $request->low_stock;
        $item->save();

         if ($item->low_stock) {
        $title = __('messages.low_stock_title'); 
        $body  = __('messages.low_stock_body', ['item' => $item->name]); 

        $firebase = (new Factory)
            ->withServiceAccount(public_path('restaurent-67df3-firebase-adminsdk-fbsvc-c42e5f0548.json'));

        $messaging = $firebase->createMessaging();

        foreach (
            User::whereIn('user_role', ['owner', 'manager','inventory_manager'])
                ->whereNotNull('fcm_token')
                ->get() as $user
        ) {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($title, $body));

            try {
                $messaging->send($message);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                \Log::error("FCM error for user {$user->id}: " . $e->getMessage());
            }
        }
    }
        return response()->json(['message' => 'Low stock updated']);
    }

    public function softDelete($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Inventory item soft deleted successfully.']);
    }

    public function restore($id)
    {
        $item = InventoryItem::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Inventory item restored successfully.']);
    }

    public function forceDelete($id)
    {
        $item = InventoryItem::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Inventory item permanently deleted.']);
    }

    public function hidden()
    {
        $trashedItems = InventoryItem::onlyTrashed()->get();
        return response()->json($trashedItems);
    }
}

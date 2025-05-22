<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\KitchenSection;
use App\Models\Table;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function salesReport(Request $request)
    {
        $range = $request->input('range');

        if (!$request->has('start_date') && !$request->has('end_date') && $range) {
            switch ($range) {
                case 'weekly':
                    $startDate = now()->subWeek()->toDateString();
                    break;
                case 'monthly':
                    $startDate = now()->subMonth()->toDateString();
                    break;
                case 'daily':
                    $startDate = now()->toDateString();
                    break;
                default:
                    $startDate = now()->subMonth()->toDateString();
            }
            $endDate = now()->toDateString();
        } else {
            $startDate = $request->input('start_date', now()->subMonth()->toDateString());
            $endDate = $request->input('end_date', now()->toDateString());
        }

        $userId = $request->input('user_id');
        $category = $request->input('category');

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'user_id' => 'nullable|exists:users,id',
            'category' => 'nullable|string|max:100',
            'range' => 'nullable|in:daily,weekly,monthly',
        ]);

        $query = Order::where('is_canceled', false)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $orders = $query->with(['items.menuItem', 'user'])->get();

        $totalSales = 0;
        $menuItemBreakdown = [];
        $waiterBreakdown = [];
        $categoryBreakdown = [];
        $dailyBreakdown = [];

        $orderItemCount = 0;          // ✅ Count of order items
        $totalQuantityHandled = 0;    // ✅ Sum of item quantities

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->status === 'canceled') {
                    continue;
                }

                $menuItem = $item->menuItem;

                if ($category && $menuItem && $menuItem->category !== $category) {
                    continue;
                }

                $menuItemId = $item->menu_item_id;
                $menuItemName = $menuItem->name ?? 'Unknown';
                $categoryName = $menuItem->category ?? 'Unknown';
                $price = $item->price;
                $quantity = $item->quantity;

                $totalSales += $price;
                $orderItemCount++;
                $totalQuantityHandled += $quantity;

                if (!isset($menuItemBreakdown[$menuItemId])) {
                    $menuItemBreakdown[$menuItemId] = [
                        'menu_item_id' => $menuItemId,
                        'name' => $menuItemName,
                        'quantity_sold' => 0,
                        'total_revenue' => 0,
                    ];
                }
                $menuItemBreakdown[$menuItemId]['quantity_sold'] += $quantity;
                $menuItemBreakdown[$menuItemId]['total_revenue'] += $price;

                if (!isset($categoryBreakdown[$categoryName])) {
                    $categoryBreakdown[$categoryName] = [
                        'category' => $categoryName,
                        'total_sales' => 0,
                    ];
                }
                $categoryBreakdown[$categoryName]['total_sales'] += $price;

                $orderDate = $order->created_at->format('Y-m-d');
                if (!isset($dailyBreakdown[$orderDate])) {
                    $dailyBreakdown[$orderDate] = [
                        'date' => $orderDate,
                        'total_sales' => 0,
                    ];
                }
                $dailyBreakdown[$orderDate]['total_sales'] += $price;
            }

            $waiter = $order->user;

            if (!$waiter) continue;

            $waiterId = $waiter->id;

            if (!isset($waiterBreakdown[$waiterId])) {
                $waiterBreakdown[$waiterId] = [
                    'user' => $waiter,
                    'total_sales' => 0,
                    //  'orders_count' => 0,
                ];
            }

            $waiterBreakdown[$waiterId]['total_sales'] += $order->total_price;
        }

        return response()->json([
            'total_sales' => $totalSales,
            'order_item_count' => $orderItemCount,
            'total_menu_items_handled' => $totalQuantityHandled,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'menu_items' => array_values($menuItemBreakdown),
            'waiters' => array_values($waiterBreakdown),
            'categories' => array_values($categoryBreakdown),
            'daily_sales' => array_values($dailyBreakdown),
        ]);
    }


    public function kitchenSectionReport(Request $request)
    {
        $range = $request->input('range');

        if (!$request->has('start_date') && !$request->has('end_date') && $range) {
            switch ($range) {
                case 'weekly':
                    $startDate = now()->subWeek()->toDateString();
                    break;
                case 'monthly':
                    $startDate = now()->subMonth()->toDateString();
                    break;
                case 'daily':
                    $startDate = now()->toDateString();
                    break;
                default:
                    $startDate = now()->subMonth()->toDateString();
            }
            $endDate = now()->toDateString();
        } else {
            $startDate = $request->input('start_date', now()->subMonth()->toDateString());
            $endDate = $request->input('end_date', now()->toDateString());
        }

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'range' => 'nullable|in:daily,weekly,monthly',
        ]);

        $sections = KitchenSection::all();

        $orderItems = \App\Models\OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        })->with('menuItem')->get();

        $sectionStats = [];

        foreach ($sections as $section) {
            $categories = $section->categories ?? [];

            $itemsHandled = 0;
            $canceledItems = 0;
            $totalItems = 0;
            $totalQuantityHandled = 0;

            foreach ($orderItems as $item) {
                $menuItemCategory = $item->menuItem->category ?? null;

                if ($menuItemCategory && in_array(strtolower($menuItemCategory), array_map('strtolower', $categories))) {
                    $totalItems++;
                    if ($item->status === 'canceled') {
                        $canceledItems++;
                    } elseif ($item->status === 'finished') {
                        $itemsHandled++;
                        $totalQuantityHandled += $item->quantity;
                    }
                }
            }

            $cancelationRate = $totalItems > 0 ? round(($canceledItems / $totalItems) * 100, 2) : 0;

            $sectionStats[] = [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'categories' => $categories,
                'order_items_handled' => $itemsHandled,
                'total_menu_items_handled' => $totalQuantityHandled,
                'canceled_items' => $canceledItems,
                'total_items' => $totalItems,
                'cancelation_rate' => $cancelationRate,
            ];
        }

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sections' => $sectionStats,
        ]);
    }

    public function popularDishesReport(Request $request)
    {
        $range = $request->input('range');
        $category = $request->input('category');

        $defaultStart = now()->subMonth()->toDateString();
        $defaultEnd = now()->toDateString();

        if ($range === 'daily') {
            $startDate = now()->toDateString();
            $endDate = now()->toDateString();
        } elseif ($range === 'weekly') {
            $startDate = now()->subDays(7)->toDateString();
            $endDate = now()->toDateString();
        } elseif ($range === 'monthly') {
            $startDate = now()->subMonth()->toDateString();
            $endDate = now()->toDateString();
        } else {
            $startDate = $request->input('start_date', $defaultStart);
            $endDate = $request->input('end_date', $defaultEnd);
        }

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'category' => 'nullable|string',
            'range' => 'nullable|in:daily,weekly,monthly',
        ]);

        $orderItems = \App\Models\OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->where('is_canceled', false)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        })->whereHas('menuItem', function ($query) use ($category) {
            if ($category) {
                $query->where('category', $category);
            }
        })->with('menuItem')->get();

        $dishStats = [];

        foreach ($orderItems as $item) {
            if ($item->status === 'canceled') {
                continue;
            }

            if (!$item->menuItem) continue;

            $id = $item->menu_item_id;
            $name = $item->menuItem->name;

            if (!isset($dishStats[$id])) {
                $dishStats[$id] = [
                    'menu_item_id' => $id,
                    'menu_item_name' => $name,
                    'category' => $item->menuItem->category,
                    'total_quantity' => 0,
                    'total_revenue' => 0,
                ];
            }

            $dishStats[$id]['total_quantity'] += $item->quantity;
            $dishStats[$id]['total_revenue'] += $item->price;
        }

        $byFrequency = collect($dishStats)->sortByDesc('total_quantity')->values()->all();
        $byRevenue = collect($dishStats)->sortByDesc('total_revenue')->values()->all();

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category' => $category,
            'range' => $range,
            'most_popular_by_frequency' => $byFrequency,
            'most_popular_by_revenue' => $byRevenue,
        ]);
    }
    public function billReport(Request $request)
    {
        $range = $request->input('range');
        $defaultStart = now()->subMonth()->toDateString();
        $defaultEnd = now()->toDateString();

        if ($range === 'daily') {
            $startDate = now()->toDateString();
            $endDate = now()->toDateString();
        } elseif ($range === 'weekly') {
            $startDate = now()->subDays(7)->toDateString();
            $endDate = now()->toDateString();
        } elseif ($range === 'monthly') {
            $startDate = now()->subMonth()->toDateString();
            $endDate = now()->toDateString();
        } else {
            $startDate = $request->input('start_date', $defaultStart);
            $endDate = $request->input('end_date', $defaultEnd);
        }

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'range' => 'nullable|in:daily,weekly,monthly',
        ]);

        $bills = Bill::with(['orders' => function ($q) {
            $q->where('is_canceled', false)->with(['items' => function ($q2) {
                $q2->where('status', '!=', 'canceled');
            }]);
        }])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->get();

        $openBills = $bills->where('status', 'open')->count();
        $paidBills = $bills->where('status', 'paid')->count();
        $totalSales = $bills->sum('final_price');
        $billsCount = $bills->count();

        $dailySales = [];
        foreach ($bills as $bill) {
            $date = $bill->created_at->format('Y-m-d');
            if (!isset($dailySales[$date])) {
                $dailySales[$date] = [
                    'date' => $date,
                    'total_sales' => 0,
                    'bills_count' => 0,
                ];
            }
            $dailySales[$date]['total_sales'] += $bill->final_price;
            $dailySales[$date]['bills_count'] += 1;
        }

        $mostExpensiveBills = $bills->sortByDesc('final_price')->take(5)->values()->map(function ($bill) {
            $validOrders = $bill->orders;

            return [
                'bill_id' => $bill->id,
                'table_number' => $bill->table_number,
                'final_price' => $bill->final_price,
                'orders_count' => $validOrders->count(),
                'items_count' => $validOrders->flatMap->items->count(),
                'total_menu_items_handled' => $validOrders->flatMap->items->sum('quantity'),
            ];
        });

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'range' => $range,
            'open_bills' => $openBills,
            'paid_bills' => $paidBills,
            'total_sales' => $totalSales,
            'bills_count' => $billsCount,
            'daily_sales' => array_values($dailySales),
            'most_expensive_bills' => $mostExpensiveBills,
        ]);
    }

    public function tableReport(Request $request)
    {
        $range = $request->input('range');
        $defaultStart = now()->subMonth()->toDateString();
        $defaultEnd = now()->toDateString();

        if ($range === 'daily') {
            $startDate = now()->toDateString();
            $endDate = now()->toDateString();
        } elseif ($range === 'weekly') {
            $startDate = now()->subDays(7)->toDateString();
            $endDate = now()->toDateString();
        } elseif ($range === 'monthly') {
            $startDate = now()->subMonth()->toDateString();
            $endDate = now()->toDateString();
        } elseif ($range === 'incoming_week') {
            $startDate = now()->toDateString();
            $endDate = now()->addWeek()->toDateString();
        } elseif ($range === 'tomorrow') {
            $startDate = now()->addDay()->toDateString();
            $endDate = $startDate;
        } else {
            $startDate = $request->input('start_date', $defaultStart);
            $endDate = $request->input('end_date', $defaultEnd);
        }

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'range' => 'nullable|in:daily,weekly,monthly,incoming_week,tomorrow',
        ]);

        $tables = Table::with(['reservations' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('reservation_date', [$startDate, $endDate]);
        }])->get();

        $report = $tables->map(function ($table) {
            $reservations = $table->reservations;

            $confirmedReservations = $reservations->where('status', 'confirmed');
            $cancelledReservations = $reservations->where('status', 'cancelled');

            $totalConfirmed = $confirmedReservations->count();
            $totalGuests = $confirmedReservations->sum('guests_number');
            $capacity = $table->capacity;

            $occupancyRate = $totalConfirmed > 0
                ? round(($totalGuests / ($capacity * $totalConfirmed)) * 100, 2)
                : 0;

            return [
                'table_number' => $table->table_number,
                'capacity' => $capacity,
                'total_reservations' => $reservations->count(),
                'confirmed_reservations' => $totalConfirmed,
                'cancelled_reservations' => $cancelledReservations->count(),
                'total_guests' => $totalGuests,
                'occupancy_rate_percent' => $occupancyRate,
            ];
        });

        return response()->json([
            'from' => $startDate,
            'to' => $endDate,
            'report' => $report
        ]);
    }
}

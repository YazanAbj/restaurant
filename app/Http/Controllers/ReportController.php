<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dailySales()
    {
        $sales = Order::whereDate('created_at', today())->sum('total_price');
        return response()->json(['total_sales' => $sales]);
    }
}

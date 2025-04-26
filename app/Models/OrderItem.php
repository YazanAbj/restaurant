<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'table_number',
        'menu_item_id',
        'quantity',
        'status',
        'price',
        'kitchen_section_id'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function kitchenSection()
    {
        return $this->belongsTo(KitchenSection::class);
    }
}

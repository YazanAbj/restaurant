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
        'kitchen_section_id',
        'notes'
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

    protected static function booted()
    {
        static::updated(function ($orderItem) {
            $order = $orderItem->order;

            $allFinished = $order->items()->where('status', '!=', 'finished')->doesntExist();

            if ($allFinished && !$order->has_been_served) {
                $order->update(['has_been_served' => true]);
            }
        });
    }
}

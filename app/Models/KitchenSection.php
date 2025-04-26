<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenSection extends Model
{
    protected $fillable = ['name', 'categories'];

    protected $casts = [
        'categories' => 'array',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

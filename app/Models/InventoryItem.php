<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'quantity',
        'unit',
        'price_per_unit',
        'supplier_name',
        'received_date',
        'expiry_date',
        'low_stock', 
        'photo',

    ];

    public function menuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'menu_item_requirements')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
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
        'low_stock_threshold',
        'photo',

    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php
// app/Models/InventoryItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'quantity',
        'unit',
        'price_per_unit',
        'supplier_name',
        'received_date',
        'expiry_date',
    ];
}

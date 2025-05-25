<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KitchenSection extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'categories'];

    protected $casts = [
        'categories' => 'array',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

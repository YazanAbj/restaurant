<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'total',
        'discount_type',
        'discount_value',
        'discount_amount',
        'final_price',
        'status',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    // Optional: helper method to calculate discount
    public function applyDiscount()
    {
        if (!$this->discount_type || !$this->discount_value) {
            $this->discount_amount = 0;
            $this->final_price = $this->total;
        } elseif ($this->discount_type === 'percentage') {
            $this->discount_amount = round(($this->total * $this->discount_value) / 100, 2);
            $this->final_price = $this->total - $this->discount_amount;
        } elseif ($this->discount_type === 'fixed') {
            $this->discount_amount = min($this->discount_value, $this->total); // Prevent negative
            $this->final_price = $this->total - $this->discount_amount;
        }

        return $this;
    }
}

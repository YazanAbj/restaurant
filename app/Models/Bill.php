<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'table_id',
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
    public function table()
    {
        return $this->belongsTo(Table::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function applyDiscount()
    {
        if (!$this->discount_type || !$this->discount_value) {
            $this->discount_amount = 0;
            $this->final_price = $this->total;
        } elseif ($this->discount_type === 'percentage') {
            $this->discount_amount = round(($this->total * $this->discount_value) / 100, 2);
            $this->final_price = $this->total - $this->discount_amount;
        } elseif ($this->discount_type === 'fixed') {
            $this->discount_amount = min($this->discount_value, $this->total);
            $this->final_price = $this->total - $this->discount_amount;
        }

        return $this;
    }

    protected static function booted()
    {
        static::deleting(function ($bill) {
            if (!$bill->isForceDeleting()) {
                $bill->orders()->each(function ($order) {
                    $order->delete();
                });
            }
        });

        static::restoring(function ($bill) {
            $bill->orders()->withTrashed()->each(function ($order) {
                $order->restore();
            });
        });
    }
}

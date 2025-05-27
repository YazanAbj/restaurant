<?php

namespace App\Models;

use App\Http\Controllers\Manager\BillController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'table_id',
        'total_price',
        'bill_id',
        'has_been_served',
        'is_canceled',
        'user_id',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::deleting(function ($order) {
            if (! $order->isForceDeleting()) {
                $order->items()->delete(); // soft delete children
            }
        });

        static::restoring(function ($order) {
            $order->items()->withTrashed()->restore();
        });
    }
}

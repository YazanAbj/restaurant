<?php

namespace App\Models;

use App\Http\Controllers\Manager\BillController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'total_price',
        'bill_id',
        'has_been_served',
        'is_canceled'

    ];


    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}

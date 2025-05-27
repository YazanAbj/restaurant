<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Table extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'capacity',
        'status'

    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

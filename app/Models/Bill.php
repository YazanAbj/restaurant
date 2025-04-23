<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'total_amount',
        'billed_at',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

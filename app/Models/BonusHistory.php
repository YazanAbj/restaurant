<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'bonus_amount',
        'reason',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

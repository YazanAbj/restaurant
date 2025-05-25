<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'age',
        'phone',
        'position',
        'shift_start',
        'shift_end',
        'salary',
        'bonus',
        'current_month_salary',
        'salary_paid',
        'notes',
        'date_joined',
        'address',
        'national_id',
        'emergency_contact',
        'photo',
        'active',
    ];


    public function bonusHistories()
    {
        return $this->hasMany(\App\Models\BonusHistory::class);
    }
}

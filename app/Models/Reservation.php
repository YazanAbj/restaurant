<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'table_id',
        'reservation_date',
        'reservation_start_time',
        'reservation_end_time',
        'guest_number',
        'notes',
        'status',
    ];

    /**
     * A reservation belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get upcoming confirmed reservations.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', now()->toDateString())
            ->where('status', 'confirmed')
            ->orderBy('reservation_date')
            ->orderBy('reservation_time');
    }

    public static function isTableAvailable($tableId, $date, $start, $end, $excludeReservationId = null)
    {
        return !Reservation::where('table_id', $tableId)
            ->where('reservation_date', $date)
            ->where('status', 'confirmed')
            ->when($excludeReservationId, fn($query) => $query->where('id', '!=', $excludeReservationId))
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('reservation_start_time', [$start, $end])
                    ->orWhereBetween('reservation_end_time', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('reservation_start_time', '<=', $start)
                            ->where('reservation_end_time', '>=', $end);
                    });
            })
            ->exists();
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}

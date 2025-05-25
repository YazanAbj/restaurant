<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'table_id',
        'reservation_date',
        'reservation_start_time',
        'guests_number',
        'guest_name',
        'guest_phone',
        'notes',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
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
*/
    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}

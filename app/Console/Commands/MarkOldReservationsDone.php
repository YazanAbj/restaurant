<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;

class MarkOldReservationsDone extends Command
{
    protected $signature = 'reservations:mark-done';
    protected $description = 'Mark reservations as done after one day';

    public function handle()
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $updated = Reservation::where('status', 'confirmed')
            ->whereDate('reservation_date',  '<=', $yesterday)
            ->update(['status' => 'done']);

        $this->info("Marked $updated reservations as done.");
    }
}

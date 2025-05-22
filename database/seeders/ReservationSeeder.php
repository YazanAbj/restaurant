<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
use App\Models\Table;
use Carbon\Carbon;

class ReservationSeeder extends Seeder
{
    public function run()
    {
        $today = Carbon::today();

        $reservations = [
            [
                'table_id' => 1,
                'reservation_date' => $today->toDateString(),
                'reservation_start_time' => '18:00:00',
                'guests_number' => 2,
                'guest_name' => 'John Doe',
                'guest_phone' => '1234567890',
                'notes' => 'Birthday celebration',
                'status' => 'confirmed',
            ],
            [
                'table_id' => 2,
                'reservation_date' => $today->toDateString(),
                'reservation_start_time' => '19:00:00',
                'guests_number' => 4,
                'guest_name' => 'Jane Smith',
                'guest_phone' => '0987654321',
                'notes' => null,
                'status' => 'confirmed',
            ],
            [
                'table_id' => 3,
                'reservation_date' => $today->addDay()->toDateString(),
                'reservation_start_time' => '20:00:00',
                'guests_number' => 5,
                'guest_name' => 'Alice Johnson',
                'guest_phone' => '1122334455',
                'notes' => 'Request a window seat',
                'status' => 'confirmed',
            ],
        ];

        foreach ($reservations as $reservation) {
            Reservation::create($reservation);
        }
    }
}

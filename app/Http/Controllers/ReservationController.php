<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function store(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_start_time' => 'required|date_format:H:i',
            'reservation_end_time' => 'required|date_format:H:i|after:reservation_start_time',
            'guest_number' => 'required|integer|min:1',
            'table_id' => 'required|integer|exists:tables,id',
            'notes' => 'nullable|string',
        ]);

        $table = Table::findOrFail($request->table_id);

        if ($request->guest_number > $table->capacity) {
            return response()->json(['message' => 'Guest number exceeds table capacity.'], 400);
        }

        if (!Reservation::isTableAvailable(
            $request->table_id,
            $request->reservation_date,
            $request->reservation_start_time,
            $request->reservation_end_time
        )) {
            return response()->json(['message' => 'This table is not available at the selected time.'], 409);
        }

        $reservation = Reservation::create([
            'user_id' => auth()->id(),
            'table_id' => $request->table_id,
            'reservation_date' => $request->reservation_date,
            'reservation_start_time' => $request->reservation_start_time,
            'reservation_end_time' => $request->reservation_end_time,
            'guest_number' => $request->guest_number,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Reservation successful.',
            'data' => $reservation
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Find the reservation
        $reservation = Reservation::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found or unauthorized.'], 404);
        }

        // Validate input
        $request->validate([
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_start_time' => 'required|date_format:H:i',
            'reservation_end_time' => 'required|date_format:H:i|after:reservation_start_time',
            'guest_number' => 'required|integer|min:1',
            'table_id' => 'required|integer|exists:tables,id',
            'notes' => 'nullable|string',
        ]);

        $table = Table::find($request->table_id);

        if (!$table) {
            return response()->json(['message' => 'Selected table does not exist.'], 404);
        }

        if ($request->guest_number > $table->capacity) {
            return response()->json(['message' => 'Guest number exceeds the table capacity.'], 400);
        }

        if (!Reservation::isTableAvailable(
            $request->table_id,
            $request->reservation_date,
            $request->reservation_start_time,
            $request->reservation_end_time
        )) {
            return response()->json(['message' => 'This table is not available at the selected time.'], 409);
        }

        // Perform update
        $reservation->update([
            'table_id' => $request->table_id,
            'reservation_date' => $request->reservation_date,
            'reservation_start_time' => $request->reservation_start_time,
            'reservation_end_time' => $request->reservation_end_time,
            'guest_number' => $request->guest_number,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Reservation updated successfully.',
            'data' => $reservation
        ], 200);
    }


    public function cancel($id)
    {
        $reservation = Reservation::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found or unauthorized.'], 404);
        }

        if ($reservation->status === 'cancelled') {
            return response()->json(['message' => 'This reservation is already cancelled.'], 400);
        }

        $reservation->status = 'cancelled';
        $reservation->save();

        return response()->json(['message' => 'Reservation cancelled successfully.']);
    }

    public function index()
    {
        $reservations = Reservation::with(['table', 'user'])->get();

        return response()->json([
            'message' => 'All reservations retrieved successfully.',
            'data' => $reservations
        ], 200);
    }

    public function show($id)
    {
        $reservation = Reservation::with(['table', 'user'])->find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }

        return response()->json([
            'message' => 'Reservation retrieved successfully.',
            'data' => $reservation
        ], 200);
    }
}

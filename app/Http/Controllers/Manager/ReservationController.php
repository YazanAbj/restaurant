<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
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

        $existingReservation = Reservation::where('user_id', auth()->id())
            ->where('table_id', $request->table_id)
            ->where('reservation_date', $request->reservation_date)
            ->where('reservation_start_time', $request->reservation_start_time)
            ->where('reservation_end_time', $request->reservation_end_time)
            ->where('status', 'confirmedt')
            ->first();

        if ($existingReservation) {
            return response()->json(['message' => 'You already have a reservation for this table and time.'], 409);
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
            'status' => 'confirmed',
        ]);

        return response()->json([
            'message' => 'Reservation successfully created and confirmed.',
            'data' => $reservation
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (auth()->user()->user_role !== 'manager') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }

        // Validate only if fields are present
        $request->validate([
            'reservation_date' => 'sometimes|date|after_or_equal:today',
            'reservation_start_time' => 'sometimes|date_format:H:i',
            'reservation_end_time' => 'sometimes|date_format:H:i|after:reservation_start_time',
            'guest_number' => 'sometimes|integer|min:1',
            'table_id' => 'sometimes|integer|exists:tables,id',
            'notes' => 'nullable|string',
        ]);

        // Use existing values if not provided in request
        $tableId = $request->input('table_id', $reservation->table_id);
        $date = $request->input('reservation_date', $reservation->reservation_date);
        $startTime = $request->input('reservation_start_time', $reservation->reservation_start_time);
        $endTime = $request->input('reservation_end_time', $reservation->reservation_end_time);
        $guestNumber = $request->input('guest_number', $reservation->guest_number);

        $table = Table::find($tableId);

        if (!$table) {
            return response()->json(['message' => 'Selected table does not exist.'], 404);
        }

        if ($guestNumber > $table->capacity) {
            return response()->json(['message' => 'Guest number exceeds the table capacity.'], 400);
        }

        if (!Reservation::isTableAvailable(
            $tableId,
            $date,
            $startTime,
            $endTime,
            $reservation->id
        )) {
            return response()->json(['message' => 'This table is not available at the selected time.'], 409);
        }

        $reservation->update([
            'table_id' => $tableId,
            'reservation_date' => $date,
            'reservation_start_time' => $startTime,
            'reservation_end_time' => $endTime,
            'guest_number' => $guestNumber,
            'notes' => $request->input('notes', $reservation->notes),
        ]);

        return response()->json([
            'message' => 'Reservation updated successfully by manager.',
            'data' => $reservation
        ], 200);
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

    public function accept($id)
    {
        $reservation = Reservation::findOrFail($id);

        // Only accept if it's pending
        if ($reservation->status !== 'pending') {
            return response()->json(['message' => 'Reservation is already processed.'], 400);
        }

        // Mark this reservation as accepted
        $reservation->status = 'confirmed';
        $reservation->save();

        // Cancel all overlapping pending reservations for the same table
        Reservation::where('id', '!=', $reservation->id)
            ->where('table_id', $reservation->table_id)
            ->where('status', 'pending')
            ->where('reservation_date', $reservation->reservation_date)
            ->where(function ($query) use ($reservation) {
                $query->whereBetween('reservation_start_time', [$reservation->reservation_start_time, $reservation->reservation_end_time])
                    ->orWhereBetween('reservation_end_time', [$reservation->reservation_start_time, $reservation->reservation_end_time])
                    ->orWhere(function ($query) use ($reservation) {
                        $query->where('reservation_start_time', '<', $reservation->reservation_start_time)
                            ->where('reservation_end_time', '>', $reservation->reservation_end_time);
                    });
            })
            ->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Reservation confirmed and conflicting ones cancelled.']);
    }

    public function reject($id)
    {
        $reservation = Reservation::findOrFail($id);


        if ($reservation->status !== 'pending') {
            return response()->json(['message' => 'Reservation is already processed.'], 400);
        }

        $reservation->status = 'cancelled';
        $reservation->save();

        return response()->json(['message' => 'Reservation rejected successfully.']);
    }

    public function cancel($id)
    {
        $reservation = Reservation::findOrFail($id);

        if (in_array($reservation->status, ['cancelled'])) {
            return response()->json([
                'message' => "This reservation is already cancelled."
            ], 400);
        }

        $reservation->status = 'cancelled';
        $reservation->save();

        return response()->json(['message' => 'Reservation cancelled by manager.']);
    }
}

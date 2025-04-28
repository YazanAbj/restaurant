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
        $request->validate([
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_start_time' => 'required|date_format:H:i',
            'guest_number' => 'required|integer|min:1',
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'string|max:20',
            'table_id' => 'required|integer|exists:tables,table_number',
            'notes' => 'nullable|string',
        ]);

        $table = Table::findOrFail($request->table_id);

        if ($request->guest_number > $table->capacity) {
            return response()->json([
                'message' => 'Guest number exceeds the table capacity.'
            ], 400);
        }


        $existingReservations = Reservation::where('table_id', $request->table_id)
            ->where('reservation_date', $request->reservation_date)
            ->where('status', 'confirmed')
            ->get();

        $overlapping = false;

        foreach ($existingReservations as $existing) {
            $existingTime = \Carbon\Carbon::createFromFormat('H:i:s', $existing->reservation_start_time);
            $newTime = \Carbon\Carbon::createFromFormat('H:i', $request->reservation_start_time);

            if ($existingTime->diffInMinutes($newTime) < 60) {
                $overlapping = true;
                break;
            }
        }

        if ($overlapping && !$request->force) {
            return response()->json([
                'message' => 'Warning: Another reservation is close to this time (less than 1 hour). Are you sure you want to continue?',
                'requires_confirmation' => true,
                'data' => $request->all()
            ], 409);
        }

        $reservation = Reservation::create([
            'user_id' => auth()->id(),
            'table_id' => $request->table_id,
            'reservation_date' => $request->reservation_date,
            'reservation_start_time' => $request->reservation_start_time,
            'guest_number' => $request->guest_number,
            'guest_name' => $request->guest_name,
            'guest_phone' => $request->guest_phone,
            'notes' => $request->notes,
            'status' => 'confirmed',
        ]);

        return response()->json([
            'message' => 'Reservation created successfully.',
            'reservation' => $reservation
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        $request->validate([
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_start_time' => 'required|date_format:H:i',
            'guest_number' => 'required|integer|min:1',
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'string|max:20',
            'table_id' => 'required|integer|exists:tables,table_number',
            'notes' => 'nullable|string',
        ]);

        $table = Table::findOrFail($request->table_id);


        if ($request->guest_number > $table->capacity) {
            return response()->json([
                'message' => 'Guest number exceeds the table capacity.'
            ], 400);
        }


        $existingReservations = Reservation::where('table_id', $request->table_id)
            ->where('reservation_date', $request->reservation_date)
            ->where('status', 'confirmed')
            ->where('id', '!=', $reservation->id)
            ->get();

        $overlapping = false;

        foreach ($existingReservations as $existing) {
            $existingTime = \Carbon\Carbon::createFromFormat('H:i:s', $existing->reservation_start_time);
            $newTime = \Carbon\Carbon::createFromFormat('H:i', $request->reservation_start_time);

            if ($existingTime->diffInMinutes($newTime) < 60) {
                $overlapping = true;
                break;
            }
        }

        if ($overlapping && !$request->force) {
            return response()->json([
                'message' => 'Warning: Another reservation is close to this time (less than 1 hour). Are you sure you want to continue?',
                'requires_confirmation' => true,
                'data' => $request->all()
            ], 409);
        }

        $reservation->update([
            'table_id' => $request->table_id,
            'reservation_date' => $request->reservation_date,
            'reservation_start_time' => $request->reservation_start_time,
            'guest_number' => $request->guest_number,
            'guest_name' => $request->guest_name,
            'guest_phone' => $request->guest_phone,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Reservation updated successfully.',
            'reservation' => $reservation
        ], 200);
    }

    //to confirm a cancelled reservation
    public function updateStatus(Request $request, $id)
    {

        $request->validate([
            'status' => 'required|string|in:confirmed',
        ]);


        $reservation = Reservation::findOrFail($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }


        if ($reservation->status === 'confirmed') {
            return response()->json([
                'message' => 'Reservation is already confirmed.'
            ], 400);
        }

        $reservation->status = $request->status;
        $reservation->save();

        return response()->json([
            'message' => 'Reservation status updated successfully.',
            'reservation' => $reservation
        ], 200);
    }


    public function index(Request $request)
    {

        $query = Reservation::with('table')->orderBy('reservation_date', 'asc')
            ->orderBy('reservation_start_time', 'asc');

        // Filter by reservation date (day)
        if ($request->has('day')) {
            $query->whereDate('reservation_date', '=', \Carbon\Carbon::parse($request->day)->toDateString());
        }

        // Filter by reservation date (week)
        if ($request->has('week')) {
            $startOfWeek = \Carbon\Carbon::parse($request->week)->startOfWeek()->toDateString();
            $endOfWeek = \Carbon\Carbon::parse($request->week)->endOfWeek()->toDateString();
            $query->whereBetween('reservation_date', [$startOfWeek, $endOfWeek]);
        }


        if ($request->has('status')) {
            $query->where('status', $request->status);
        }


        $reservations = $query->paginate(10);
        return response()->json([
            'reservations' => $reservations
        ], 200);
    }


    public function show($id)
    {
        $reservation = Reservation::with('table')->findOrFail($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }

        return response()->json([
            'reservation' => $reservation
        ], 200);
    }

    //to cancel a confirmed reservation
    public function cancel($id)
    {
        $reservation = Reservation::findOrFail($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }

        if ($reservation->status === 'cancelled') {
            return response()->json([
                'message' => 'Reservation is already cancelled.'
            ], 400);
        }

        $reservation->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Reservation cancelled successfully.',
        ], 200);
    }


    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);

        $reservation->delete();

        return response()->json([
            'message' => 'Reservation deleted successfully.'
        ], 200);
    }
}

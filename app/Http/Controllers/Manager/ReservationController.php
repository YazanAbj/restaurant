<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\RestaurantHour;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationController extends Controller
{

    public function index(Request $request)
    {

        $query = Reservation::with('table')->orderBy('reservation_date', 'asc')
            ->orderBy('reservation_start_time', 'asc');

        if ($request->has('day')) {
            $query->whereDate('reservation_date', '=', \Carbon\Carbon::parse($request->day)->toDateString());
        }

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

    public function store(Request $request)
    {
        $request->validate([
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_start_time' => 'required|date_format:H:i',
            'guests_number' => 'required|integer|min:1',
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'string|max:20',
            'table_id' => 'required|integer|exists:tables,id',
            'notes' => 'nullable|string',
        ]);

        $reservationDateTime = Carbon::createFromFormat('Y-m-d H:i', $request->reservation_date . ' ' . $request->reservation_start_time);

        if ($reservationDateTime->isPast()) {
            return response()->json([
                'message' => 'Reservation time must be in the future.'
            ], 400);
        }

        $table = Table::findOrFail($request->table_id);

        if ($request->guests_number > $table->capacity) {
            return response()->json([
                'message' => 'Guest number exceeds the table capacity.'
            ], 400);
        }

        if (Reservation::hasThreeConsecutiveCancellations($request->guest_phone) && !$request->force_cancellation_warning) {
            return response()->json([
                'message' => 'Warning: This guest has canceled their last 3 reservations. Do you want to proceed?',
                'requires_confirmation' => true,
                'reason' => 'cancellation_warning',
                'data' => $request->all()
            ], 409);
        }


        // ✅ Check for overlapping reservations
        $existingReservations = Reservation::where('table_id', $request->table_id)
            ->where('reservation_date', $request->reservation_date)
            ->where('status', 'confirmed')
            ->get();

        $overlapping = false;

        foreach ($existingReservations as $existing) {
            $existingStart = Carbon::createFromFormat('Y-m-d H:i:s', $existing->reservation_date . ' ' . $existing->reservation_start_time);
            $existingEnd = $existingStart->copy()->addHour();

            $newStart = Carbon::createFromFormat('Y-m-d H:i', $request->reservation_date . ' ' . $request->reservation_start_time);
            $newEnd = $newStart->copy()->addHour();

            if ($newStart->lt($existingEnd) && $existingStart->lt($newEnd)) {
                $overlapping = true;
                break;
            }
        }

        if ($overlapping && !$request->force_time_conflict) {
            return response()->json([
                'message' => 'Warning: Another reservation is close to this time (less than 1 hour). Are you sure you want to continue?',
                'requires_confirmation' => true,
                'reason' => 'time_conflict',
                'data' => $request->all()
            ], 409);
        }

        $reservation = Reservation::create([
            'user_id' => auth()->id(),
            'table_id' => $request->table_id,
            'reservation_date' => $request->reservation_date,
            'reservation_start_time' => $request->reservation_start_time,
            'guests_number' => $request->guests_number,
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
            'guests_number' => 'required|integer|min:1',
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'string|max:20',
            'table_id' => 'required|integer|exists:tables,id',
            'notes' => 'nullable|string',
        ]);

        $reservationDateTime = Carbon::createFromFormat('Y-m-d H:i', $request->reservation_date . ' ' . $request->reservation_start_time);

        if ($reservationDateTime->isPast()) {
            return response()->json([
                'message' => 'Reservation time must be in the future.'
            ], 400);
        }

        $table = Table::findOrFail($request->table_id);

        if ($request->guests_number > $table->capacity) {
            return response()->json([
                'message' => 'Guest number exceeds the table capacity.'
            ], 400);
        }

        // ✅ Check for 3 consecutive cancellations
        if (Reservation::hasThreeConsecutiveCancellations($request->guest_phone) && !$request->force_cancellation_warning) {
            return response()->json([
                'message' => 'Warning: This guest has canceled their last 3 reservations. Do you want to proceed?',
                'requires_confirmation' => true,
                'reason' => 'cancellation_warning',
                'data' => $request->all()
            ], 409);
        }

        // ✅ Check for overlapping reservations
        $existingReservations = Reservation::where('table_id', $request->table_id)
            ->where('reservation_date', $request->reservation_date)
            ->where('status', 'confirmed')
            ->where('id', '!=', $reservation->id)
            ->get();

        $overlapping = false;

        $newStart = Carbon::createFromFormat('Y-m-d H:i', $request->reservation_date . ' ' . $request->reservation_start_time);
        $newEnd = $newStart->copy()->addHour();

        foreach ($existingReservations as $existing) {
            $existingStart = Carbon::createFromFormat('Y-m-d H:i:s', $existing->reservation_date . ' ' . $existing->reservation_start_time);
            $existingEnd = $existingStart->copy()->addHour();

            if ($newStart->lt($existingEnd) && $existingStart->lt($newEnd)) {
                $overlapping = true;
                break;
            }
        }

        if ($overlapping && !$request->force_time_conflict) {
            return response()->json([
                'message' => 'Warning: Another reservation is close to this time (less than 1 hour). Are you sure you want to continue?',
                'requires_confirmation' => true,
                'reason' => 'time_conflict',
                'data' => $request->all()
            ], 409);
        }

        $reservation->update([
            'table_id' => $request->table_id,
            'reservation_date' => $request->reservation_date,
            'reservation_start_time' => $request->reservation_start_time,
            'guests_number' => $request->guests_number,
            'guest_name' => $request->guest_name,
            'guest_phone' => $request->guest_phone,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Reservation updated successfully.',
            'reservation' => $reservation
        ], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:confirmed,done',
        ]);

        $reservation = Reservation::findOrFail($id);

        if ($reservation->status === $request->status) {
            return response()->json([
                'message' => 'Reservation is already ' . $request->status . '.'
            ], 400);
        }

        $newStatus = $request->status;

        if ($reservation->status === 'done' && $newStatus === 'confirmed') {
            if (Carbon::parse($reservation->reservation_date)->isPast()) {
                return response()->json([
                    'message' => 'Cannot revert to confirmed. The reservation date has already passed.'
                ], 400);
            }
        }

        // Update status
        $reservation->status = $newStatus;
        $reservation->save();

        return response()->json([
            'message' => 'Reservation status updated successfully.',
            'reservation' => $reservation
        ], 200);
    }



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


    public function softDelete($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();

        return response()->json(['message' => 'Reservation soft deleted.']);
    }

    public function forceDelete($id)
    {
        $reservation = Reservation::withTrashed()->findOrFail($id);
        $reservation->forceDelete();

        return response()->json(['message' => 'Reservation permanently deleted.']);
    }

    public function restore($id)
    {
        $reservation = Reservation::withTrashed()->findOrFail($id);
        $reservation->restore();

        return response()->json(['message' => 'Reservation restored successfully.']);
    }


    public function hidden()
    {
        $trashedItems = Reservation::onlyTrashed()->get();
        return response()->json($trashedItems);
    }
}

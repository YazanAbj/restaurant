<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Table;

class TableController extends Controller
{

    public function getTablesByStatus(Request $request)
    {
        $status = $request->query('status');

        if ($status && !in_array($status, ['free', 'occupied'])) {
            return response()->json(['message' => 'Invalid status. Use "free" or "occupied".'], 400);
        }

        $tables = Table::when($status, function ($query) use ($status) {
            return $query->where('status', $status);
        })->get();

        return response()->json([
            'message' => $status ? "Tables with status '{$status}'" : 'All tables',
            'data' => $tables
        ]);
    }


    public function show($tableNumber)
    {
        $table = Table::where('table_number', $tableNumber)->firstOrFail();

        return response()->json(['table' => $table]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|string',
            'capacity' => 'required|integer|min:1',
        ]);


        if (Table::where('table_number', $validated['table_number'])->exists()) {
            return response()->json([
                'message' => 'Table with this number already exists.'
            ], 409);
        }

        $table = Table::create($validated);

        return response()->json([
            'message' => 'Table created successfully.',
            'table' => $table,
        ], 201);
    }


    public function update(Request $request, $tableNumber)
    {
        $table = Table::where('table_number', $tableNumber)->firstOrFail();

        $validated = $request->validate([
            'capacity' => 'sometimes|required|integer|min:1',
            'force' => 'sometimes|boolean',
        ]);

        if (isset($validated['capacity']) && $validated['capacity'] != $table->capacity) {
            $today = now()->toDateString();

            $hasReservations = $table->reservations()
                ->where('reservation_date', '>=', $today)
                ->where('reservation_start_time', '>', now()->toTimeString())
                ->exists();

            if ($hasReservations && empty($validated['force'])) {
                return response()->json([
                    'message' => 'Table has upcoming reservations. Use "force: true" to update anyway.',
                    'warning' => true,
                ], 409);
            }
        }

        $table->update($validated);

        return response()->json([
            'message' => 'Table updated successfully.',
            'table' => $table,
        ]);
    }


    public function updateStatus(Request $request, $tableNumber)
    {
        $request->validate([
            'status' => 'required|in:free,occupied',
        ]);

        $table = Table::where('table_number', $tableNumber)->first();

        if (!$table) {
            return response()->json(['message' => 'Table not found.'], 404);
        }

        $table->status = $request->status;
        $table->save();

        return response()->json([
            'message' => "Table status updated successfully.",
            'data' => $table
        ]);
    }

    public function softDelete(Request $request, $id)
    {
        $table = Table::findOrFail($id);

        $force = $request->boolean('force', false);
        $today = now()->toDateString();

        $hasReservations = $table->reservations()
            ->where('reservation_date', '>=', $today)
            ->where('reservation_start_time', '>', now()->toTimeString())
            ->exists();

        if ($hasReservations && !$force) {
            return response()->json([
                'message' => 'Table has upcoming reservations. Use "force: true" to delete anyway.',
                'warning' => true,
            ], 409);
        }

        $table->delete();

        return response()->json(['message' => 'Table soft deleted.']);
    }

    public function forceDelete(Request $request, $id)
    {
        $table = Table::withTrashed()->findOrFail($id);

        $force = $request->boolean('force', false);
        $today = now()->toDateString();

        $hasReservations = $table->reservations()
            ->where('reservation_date', '>=', $today)
            ->where('reservation_start_time', '>', now()->toTimeString())
            ->exists();

        if ($hasReservations && !$force) {
            return response()->json([
                'message' => 'Table has upcoming reservations. Use "force: true" to force delete.',
                'warning' => true,
            ], 409);
        }

        $table->forceDelete();

        return response()->json(['message' => 'Table permanently deleted.']);
    }

    public function restore($id)
    {
        $table = Table::withTrashed()->findOrFail($id);
        $table->restore();

        return response()->json(['message' => 'Table restored successfully.']);
    }

    public function hidden()
    {
        $trashedItems = Table::onlyTrashed()->get();
        return response()->json($trashedItems);
    }
}

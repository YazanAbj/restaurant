<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Table;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date');
        $start = $request->query('start_time');
        $end = $request->query('end_time');

        $tables = Table::all()->filter(function ($table) use ($date, $start, $end) {
            return Reservation::isTableAvailable($table->id, $date, $start, $end);
        });

        return response()->json($tables->values());
    }
    // POST /api/tables
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|string',
            'capacity' => 'required|integer|min:1',
        ]);

        // Check if the table_number already exists
        if (Table::where('table_number', $validated['table_number'])->exists()) {
            return response()->json([
                'message' => 'Table with this number already exists.'
            ], 409);  // 409 Conflict status
        }

        $table = Table::create($validated);

        return response()->json([
            'message' => 'Table created successfully.',
            'table' => $table,
        ], 201);
    }


    // PUT /api/tables/{id}
    public function update(Request $request, $id)
    {
        $table = Table::findOrFail($id);

        $validated = $request->validate([
            'capacity' => 'sometimes|required|integer|min:1',
        ]);

        $table->update($validated);

        return response()->json([
            'message' => 'Table updated successfully.',
            'table' => $table,
        ]);
    }
}

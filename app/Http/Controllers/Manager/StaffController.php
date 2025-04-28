<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BonusHistory;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    public function index()
    {
        return response()->json(Staff::all());
    }



    public function show(Staff $staff)
{
    $staff->load('bonusHistories'); // Load the related bonuses

    return response()->json($staff);
}


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer',
            'phone' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i',
            'salary' => 'required|integer',
            'bonus' => 'nullable|integer',
            'notes' => 'nullable|string',
            'date_joined' => 'required|date',
            'address' => 'required|string|max:255',
            'national_id' => 'required|string|max:20',
            'emergency_contact' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'active' => 'boolean',
        ]);

        if ($request->hasFile('photo')) {
            $validatedData['photo'] = $request->file('photo')->store('staff', 'public');
        }

        $validatedData['bonus'] = $validatedData['bonus'] ?? 0;
        $validatedData['current_month_salary'] = $validatedData['salary'] + $validatedData['bonus'];
        $validatedData['salary_paid'] = false;

        $staff = Staff::create($validatedData);

        return response()->json($staff, 201);
    }

    public function update(Request $request, Staff $staff)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'age' => 'sometimes|integer',
            'phone' => 'sometimes|string|max:255',
            'position' => 'sometimes|string|max:255',
            'shift_start' => 'sometimes|date_format:H:i',
            'shift_end' => 'sometimes|date_format:H:i',
            'salary' => 'sometimes|integer',
            'bonus' => 'nullable|integer',
            'notes' => 'nullable|string',
            'date_joined' => 'sometimes|date',
            'address' => 'sometimes|string|max:255',
            'national_id' => 'sometimes|string|max:20',
            'emergency_contact' => 'sometimes|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'active' => 'boolean',
        ]);

        if ($request->hasFile('photo')) {
            if ($staff->photo && Storage::disk('public')->exists($staff->photo)) {
                Storage::disk('public')->delete($staff->photo);
            }
            $validatedData['photo'] = $request->file('photo')->store('staff', 'public');
        }

        if (isset($validatedData['salary']) || isset($validatedData['bonus'])) {
            $salary = $validatedData['salary'] ?? $staff->salary;
            $bonus = $validatedData['bonus'] ?? $staff->bonus;
            $validatedData['current_month_salary'] = $salary + $bonus;
        }

        $staff->update($validatedData);

        return response()->json($staff);
    }

    public function destroy(Staff $staff)
    {
        if ($staff->photo && Storage::disk('public')->exists($staff->photo)) {
            Storage::disk('public')->delete($staff->photo);
        }

        $staff->delete();

        return response()->json(['message' => 'Staff deleted successfully']);
    }



    


    public function applyBonus(Request $request, Staff $staff)
    {
        // Validate incoming bonus value
        $validatedData = $request->validate([
            'bonus' => 'required|numeric',
            'reason' => 'nullable|string|max:255',
        ]);

        // Update the current month salary of the staff
        $staff->bonus = $staff->bonus + $validatedData['bonus'];
        $staff->current_month_salary = $staff->salary + $staff->bonus;
        $staff->save();

        // Create a new entry in the BonusHistory table
        BonusHistory::create([
            'staff_id' => $staff->id,
            'bonus_amount' => $validatedData['bonus'],
            'reason' => $validatedData['reason'] ?? 'No reason provided',
        ]);

        // Return response
        return response()->json([
            'message' => 'Bonus applied successfully',
            'staff' => $staff,
        ]);
    }

    public function updateBonus(Request $request, BonusHistory $bonusHistory)
    {
        $validatedData = $request->validate([
            'bonus' => 'required|numeric',
            'reason' => 'nullable|string|max:255',
        ]);
    
        // Get old bonus to reverse it
        $oldBonus = $bonusHistory->bonus_amount;
        $staff = $bonusHistory->staff;
    
        // Update bonus history record
        $bonusHistory->update([
            'bonus_amount' => $validatedData['bonus'],
            'reason' => $validatedData['reason'] ?? $bonusHistory->reason,
        ]);
    
        // Update staff bonus and salary
        $staff->bonus = $staff->bonus - $oldBonus + $validatedData['bonus'];
        $staff->current_month_salary = $staff->salary + $staff->bonus;
        $staff->save();
    
        return response()->json([
            'message' => 'Bonus updated successfully.',
            'bonus' => $bonusHistory,
            'staff' => $staff,
        ]);
    }
    
    public function deleteBonus(BonusHistory $bonusHistory)
{
    $staff = $bonusHistory->staff;

    // Remove bonus amount
    $staff->bonus -= $bonusHistory->bonus_amount;
    $staff->current_month_salary = $staff->salary + $staff->bonus;
    $staff->save();

    // Delete bonus history record
    $bonusHistory->delete();

    return response()->json([
        'message' => 'Bonus deleted successfully.',
        'staff' => $staff,
    ]);
}

public function bonusindex()
{
    return response()->json(BonusHistory::all());
}
}
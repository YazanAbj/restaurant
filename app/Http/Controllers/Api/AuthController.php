<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $authUser = auth()->user();

        if (!in_array($authUser->user_role, ['owner', 'manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|unique:users,email',
            'password'   => 'required|string|confirmed|min:6',
            'user_role'       => 'required|in:owner,manager,waiter,chef,reservation_manager,inventory_manager',
        ]);

        // If manager tries to create another owner or manager, block
        if ($authUser->user_role === 'manager' && in_array($request->user_role, ['owner', 'manager'])) {
            return response()->json(['message' => 'Managers cannot create owners or other managers.'], 403);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'user_role'       => $request->user_role,
        ]);

        return response()->json(['message' => 'User registered successfully.']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $authUser = auth()->user();

        // Must be owner or manager
        if (!in_array($authUser->user_role, ['owner', 'manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Manager cannot update owners or other managers
        if ($authUser->user_role === 'manager' && in_array($user->user_role, ['owner', 'manager'])) {
            return response()->json(['message' => 'Managers cannot update owners or other managers.'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $user->id,
            'password'   => 'sometimes|confirmed|min:6',
            'user_role'       => 'sometimes|in:owner,manager,waiter,chef,reservation_manager,inventory_manager',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json(['message' => 'User updated successfully.', 'user' => $user]);
    }

    public function index(Request $request)
    {
        $authUser = auth()->user();

        if (!in_array($authUser->user_role, ['owner', 'manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = User::query();

        // Managers can't see other managers or owners
        if ($authUser->user_role === 'manager') {
            $query->whereNotIn('user_role', ['owner', 'manager']);
        }

        // Optional filtering by user_role
        if ($request->has('user_role')) {
            $query->where('user_role', $request->user_role);
        }

        $users = $query->get();

        return response()->json($users);
    }

    public function show($id)
    {
        $authUser = auth()->user();

        if (!in_array($authUser->user_role, ['owner', 'manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Manager cannot view other managers or owners
        if ($authUser->user_role === 'manager' && in_array($user->user_role, ['owner', 'manager'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'logged out']);
    }

    public function softDelete($id)
    {
        $authUser = auth()->user();
        $target = User::findOrFail($id);

        if (!$this->canDelete($authUser, $target)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $target->delete();

        return response()->json(['message' => 'User soft deleted successfully.']);
    }

    public function restore($id)
    {
        $authUser = auth()->user();
        $target = User::withTrashed()->findOrFail($id);

        if (!$this->canDelete($authUser, $target)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $target->restore();

        return response()->json(['message' => 'User restored successfully.']);
    }

    public function forceDelete($id)
    {
        $authUser = auth()->user();
        $target = User::withTrashed()->findOrFail($id);

        if (!$this->canDelete($authUser, $target)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $target->forceDelete();

        return response()->json(['message' => 'User permanently deleted.']);
    }

    public function hidden()
    {
        $trashedItems = User::onlyTrashed()->get();
        return response()->json($trashedItems);
    }
    private function canDelete($authUser, $target)
    {
        if ($authUser->user_role === 'owner') {
            return true;
        }

        if ($authUser->user_role === 'manager') {
            return !in_array($target->user_role, ['owner', 'manager']);
        }

        return false;
    }


    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => 'FCM token updated successfully.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Create a new user. [cite: 59, 60]
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users'], [cite: 61]
            'password' => ['required', 'string', 'min:8'], [cite: 59]
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'], [cite: 59]
            'firstName' => ['nullable', 'string', 'max:255'], [cite: 59]
            'lastName' => ['nullable', 'string', 'max:255'], [cite: 59]
            'roleName' => ['required', 'string', Rule::in(['STUDENT', 'TEACHER', 'ADMINISTRATOR'])], [cite: 59]
        ]);

        try {
            $user = User::create([
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'email' => $request->email,
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'role_name' => $request->roleName,
                'status' => 'ACTIVE',
            ]);

            return response()->json([
                'userId' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'roleName' => $user->role_name,
                'status' => $user->status,
            ], 201); // 201 Created [cite: 60]

        } catch (\Exception $e) {
            Log::error("Failed to create user: " . $e->getMessage());
            return response()->json(['message' => 'Could not create user.'], 500);
        }
    }

    /**
     * Retrieve a single user's details. [cite: 61]
     */
    public function show($userId)
    {
        $user = User::where('id', $userId)->first(); // Use first() for direct match

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404); [cite: 62]
        }

        return response()->json([
            'userId' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'roleName' => $user->role_name,
            'status' => $user->status,
        ], 200); // 200 OK [cite: 61]
    }

    /**
     * Retrieve a list of users. [cite: 62]
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role_name', $request->input('role')); [cite: 63]
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status')); [cite: 63]
        }

        // Basic pagination (Laravel handles page and size parameters by default)
        $users = $query->paginate($request->input('size', 15), ['*'], 'page', $request->input('page', 1)); [cite: 63]

        if ($users->isEmpty()) {
            return response()->json([], 204); // 204 No Content [cite: 64]
        }

        return response()->json($users->items(), 200); // Array of user objects [cite: 64]
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // POST /auth/login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = $user->createToken('AccessToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ], 200);
    }

    // POST /auth/signup
    public function signup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8'
        ]);

        $normalizedEmail = strtolower(trim($validated['email']));

        if (User::where('email', $normalizedEmail)->exists()) {
            return response()->json([
                'message' => 'このメールアドレスは既に登録されています。',
                'data' => null,
            ], 403);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $normalizedEmail,
            'password' => Hash::make($validated['password']),
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('AccessToken')->plainTextToken;

        return response()->json([
            'message' => 'User created successfully. Please verify your email address.',
            'token' => $token,
            'user' => $user
        ], 201);
    }

    // POST /auth/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}

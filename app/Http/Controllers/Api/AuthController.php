<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // POST /auth/login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|a',
            'password' => 'required|min:8',
        ]);

        $user = User::where('email', $request->email)->first();
        // ユーザーが存在しない、またはパスワードが一致しない場合は401
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $token = $user->createToken('AccessToken')->plainTextToken;
        return response()->json(['token' => $token], 200);
    }

    // POST /auth/signup
    public function signup(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        // 入力内容のバリデーション
        $validated = $request->validate([
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // データベースにインサート
        User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'User created successfully'
        ], 201);
    }
    
    // POST /auth/logout
    public function logout(Request $request)
    {
        Auth::guard('api')->logout();
        // トークンを無効化
        $request->user()->currentAccessToken()->delete();
        // セッションをクリア
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // レスポンスを返す
        return response()->json(['message' => 'Logged out successfully']);
    }
}
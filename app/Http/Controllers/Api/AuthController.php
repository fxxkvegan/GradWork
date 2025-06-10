<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
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
        // 実際のアプリケーションでは、JWTやOAuthなどのトークン生成方法を使用することが一般的です。

        $user = User::where('email', $request->email)->first();
        // ユーザーが存在しない、またはパスワードが一致しない場合は401
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return response()->json(['token' => 'dummy_token']);
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

        User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // TODO: ユーザー新規登録処理
        return response()->json([
            'message' => 'User created successfully'
        ], 201);
    }
    
    // POST /auth/logout
    public function logout(Request $request)
    {
        // TODO: ログアウト処理
        return response()->json(['message' => 'Logged out successfully']);
    }
}
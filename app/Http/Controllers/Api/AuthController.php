<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // POST /auth/login
    public function login(Request $request)
    {
        // TODO: ログイン認証処理
        return response()->json(['token' => 'dummy_token']);
    }

    // POST /auth/signup
    public function signup(Request $request)
    {
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
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // GET /users/me
    public function profile()
    {
        // TODO: 自分のプロフィール取得処理
        return response()->json([
            'message' => 'User profile',
            'data' => [] // ユーザー情報
        ]);
    }

    // PUT /users/me
    public function updateProfile(Request $request)
    {
        // TODO: プロフィール更新処理
        return response()->json([
            'message' => 'Profile updated',
            'data' => [] // 更新後のユーザー情報
        ]);
    }

    // GET /users/me/settings
    public function getSettings()
    {
        // TODO: ユーザー設定の取得処理
        return response()->json([
            'message' => 'User settings',
            'data' => [] // 設定情報
        ]);
    }

    // PUT /users/me/settings
    public function updateSettings(Request $request)
    {
        // TODO: ユーザー設定の更新処理
        return response()->json([
            'message' => 'Settings updated',
            'data' => [] // 更新後の設定情報
        ]);
    }

    // GET /users/me/history
    public function history()
    {
        // TODO: ユーザー閲覧履歴の取得処理
        return response()->json([
            'message' => 'User history',
            'data' => [] // 閲覧履歴データ
        ]);
    }
}
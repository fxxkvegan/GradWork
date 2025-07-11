<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // GET /users/me
    public function profile($userId)
    {
        // TODO: 自分のプロフィール取得処理
        $userId = intval($userId);
        if (!is_numeric($userId) || $userId <= 0) {
            return response()->json([
                'message' => 'Invalid user ID',
                'data' => null
            ], 400);
        }
        $responseData = User::find($userId);
        if (!$responseData) {
            return response()->json([
                'message' => 'User not found',
                'data' => null
            ], 404);
        }
        // レスポンスデータの整形
        $responseData = [
            'id' => $responseData->id,
            'name' => $responseData->name,
            'email' => $responseData->email,
            'avatarUrl' => $responseData->avatar_url,
            'locale' => $responseData->locale,
            'theme' => $responseData->theme,
        ]; 
        
        return response()->json([
            'message' => 'User profile',
            'data' => $responseData // ユーザー情報
        ]);
    }

    // PUT /users/me
    public function updateProfile($userId)
    {
        $userId = intval($userId);
        if (!is_numeric($userId) || $userId <= 0) {
            return response()->json([
                'message' => 'Invalid user ID',
                'data' => null
            ], 400);
        }
        $responseData = User::find($userId);
        if (!$responseData) {
            return response()->json([
                'message' => 'User not found',
                'data' => null
            ], 404);
        }
        $responseData->name = request('name', $responseData->name);
        $responseData->email = request('email', $responseData->email);  
        $responseData->avatar_url = request('avatarUrl', $responseData->avatar_url);
        $responseData->locale = request('locale', $responseData->locale);
        $responseData->theme = request('theme', $responseData->theme);
        $responseData->save();
        // レスポンスデータの整形
        $responseData = [
            'id' => $responseData->id,
            'name' => $responseData->name,
            'email' => $responseData->email,
            'avatarUrl' => $responseData->avatar_url,
            'locale' => $responseData->locale,
            'theme' => $responseData->theme,
        ];

        return response()->json([
            'message' => 'Profile updated',
            'data' => $responseData // 更新後のユーザー情報
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
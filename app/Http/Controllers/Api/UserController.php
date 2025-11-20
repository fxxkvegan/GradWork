<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function allusers()
    {
        try {
            $users = User::get();
            
            return response()->json([
                'message' => 'All users retrieved successfully',
                'data' => $users
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // GET /users/me
    public function profile()
    {
        // TODO: 自分のプロフィール取得処理
        $userId = Auth::id(); // 現在のユーザーIDを取得
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
            'displayName' => $responseData->display_name,
            'email' => $responseData->email,
            'avatarUrl' => $responseData->avatar_url,
            'headerUrl' => $responseData->header_url,
            'bio' => $responseData->bio,
            'location' => $responseData->location,
            'website' => $responseData->website,
            'birthday' => $responseData->birthday,
            'locale' => $responseData->locale,
            'theme' => $responseData->theme,
        ]; 
        
        return response()->json([
            'message' => 'User profile',
            'data' => $responseData // ユーザー情報
        ]);
    }

    // PUT /users/me
    public function updateProfile(Request $request)
    {
        $userId = Auth::id(); // 現在のユーザーIDを取得
        $userId = intval($userId);
        if (!is_numeric($userId) || $userId <= 0) {
            return response()->json([
                'message' => 'Invalid user ID',
                'data' => null
            ], 400);
        }
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'data' => null
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users')->ignore($user->id),
            ],
            'displayName' => ['nullable', 'string', 'max:50'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'avatar' => ['nullable', 'image', 'max:5120'],
            'avatarUrl' => ['nullable', 'url', 'max:255'],
            'header' => ['nullable', 'image', 'max:9216'],
            'headerUrl' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'locale' => ['nullable', 'string', 'max:10'],
            'theme' => ['nullable', 'string', 'max:20'],
        ]);

        $avatarUploaded = false;
        if ($request->hasFile('avatar')) {
            $uploadedAvatar = $request->file('avatar');
            if ($uploadedAvatar !== null && $uploadedAvatar->isValid()) {
                $storedPath = $uploadedAvatar->store('avatars', 'public');
                $this->deleteStoredFile($user->avatar_url);
                $user->avatar_url = Storage::disk('public')->url($storedPath);
                $avatarUploaded = true;
            }
        }

        $headerUploaded = false;
        if ($request->hasFile('header')) {
            $uploadedHeader = $request->file('header');
            if ($uploadedHeader !== null && $uploadedHeader->isValid()) {
                $storedHeaderPath = $uploadedHeader->store('headers', 'public');
                $this->deleteStoredFile($user->header_url);
                $user->header_url = Storage::disk('public')->url($storedHeaderPath);
                $headerUploaded = true;
            }
        }

        if (array_key_exists('name', $validated)) {
            $name = $this->sanitizeNullableString($validated['name']);
            if ($name !== null) {
                $user->name = $name;
            }
        }
        if (array_key_exists('displayName', $validated)) {
            $user->display_name = $this->sanitizeNullableString($validated['displayName']);
        }
        if (array_key_exists('email', $validated)) {
            $email = $this->sanitizeNullableString($validated['email']);
            if ($email !== null) {
                $user->email = $email;
            }
        }
        if (!$avatarUploaded && array_key_exists('avatarUrl', $validated)) {
            $user->avatar_url = $this->sanitizeNullableString($validated['avatarUrl']);
        }
        if (!$headerUploaded && array_key_exists('headerUrl', $validated)) {
            $user->header_url = $this->sanitizeNullableString($validated['headerUrl']);
        }
        if (array_key_exists('bio', $validated)) {
            $user->bio = $this->sanitizeNullableString($validated['bio']);
        }
        if (array_key_exists('location', $validated)) {
            $user->location = $this->sanitizeNullableString($validated['location']);
        }
        if (array_key_exists('website', $validated)) {
            $user->website = $this->sanitizeNullableString($validated['website']);
        }
        if (array_key_exists('birthday', $validated)) {
            $user->birthday = $this->sanitizeNullableString($validated['birthday']);
        }
        if (array_key_exists('locale', $validated)) {
            $locale = $this->sanitizeNullableString($validated['locale']);
            if ($locale !== null) {
                $user->locale = $locale;
            }
        }
        if (array_key_exists('theme', $validated)) {
            $theme = $this->sanitizeNullableString($validated['theme']);
            if ($theme !== null) {
                $user->theme = $theme;
            }
        }

        $user->save();

        $responseData = [
            'id' => $user->id,
            'name' => $user->name,
            'displayName' => $user->display_name,
            'email' => $user->email,
            'avatarUrl' => $user->avatar_url,
            'headerUrl' => $user->header_url,
            'bio' => $user->bio,
            'location' => $user->location,
            'website' => $user->website,
            'birthday' => $user->birthday,
            'locale' => $user->locale,
            'theme' => $user->theme,
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
        $userId = Auth::id(); // 現在のユーザーIDを取得
        if (!$userId) {
            return response()->json([
                'message' => 'User not authenticated',
                'data' => null
            ], 401);
        };
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'data' => null
            ], 404);
        };
        // locale と theme のみを取得
        $settings = [
            'locale' => $user->locale,
            'theme' => $user->theme,
        ];

        return response()->json([
            'message' => 'User settings',
            'data' => $settings // 設定情報
        ]);
    }

    // PUT /users/me/settings
    public function updateSettings(Request $request)
    {
        // TODO: ユーザー設定の更新処理
        $userId = Auth::id(); // 現在のユーザーIDを取得
        if (!$userId) {
            return response()->json([
                'message' => 'User not authenticated',
                'data' => null
            ], 401);
        };
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'data' => null
            ], 404);
        };
        // locale と theme の更新
        $user->locale = $request->input('locale', $user->locale);
        $user->theme = $request->input('theme', $user->theme);
        $user->save();
        
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

    private function sanitizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function deleteStoredFile(?string $currentUrl): void
    {
        if ($currentUrl === null) {
            return;
        }

        $path = parse_url($currentUrl, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/storage/')) {
            return;
        }

        $relativePath = ltrim(substr($path, strlen('/storage/')), '/');
        if ($relativePath === '') {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }
}
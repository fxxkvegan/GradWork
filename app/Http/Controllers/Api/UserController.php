<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewNotificationRead;
use App\Models\User;
use App\Support\Presenters\ReviewNotificationPresenter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

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
    public function show(User $user)
    {
        $viewer = $this->authenticatedUser();
        $user->loadCount(['products', 'followers', 'following']);

        return response()->json([
            'message' => 'User profile',
            'data' => $this->formatUserProfileResponse($user, false, $viewer)
        ]);
    }

    // GET /users/me
    public function profile()
    {
        // TODO: 自分のプロフィール取得処理
        $user = $this->authenticatedUser();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $user->loadCount(['products', 'followers', 'following']);

        return response()->json([
            'message' => 'User profile',
            'data' => $this->formatUserProfileResponse($user, true, $user) // ユーザー情報
        ]);
    }

    // PUT /users/me
    public function updateProfile(Request $request)
    {
        $user = $this->authenticatedUser();
        if (!$user) {
            return $this->unauthorizedResponse();
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
        $user->refresh()->loadCount(['products', 'followers', 'following']);

        return response()->json([
            'message' => 'Profile updated',
            'data' => $this->formatUserProfileResponse($user, true, $user) // 更新後のユーザー情報
        ]);
    }

    public function follow(User $user)
    {
        $viewer = $this->authenticatedUser();
        if (!$viewer) {
            return $this->unauthorizedResponse();
        }

        if ($viewer->id === $user->id) {
            return response()->json([
                'message' => '自分をフォローすることはできません',
                'data' => null,
            ], 422);
        }

        $viewer->following()->syncWithoutDetaching([$user->id]);
        $user->loadCount(['products', 'followers', 'following']);

        return response()->json([
            'message' => 'User followed',
            'data' => $this->formatUserProfileResponse($user, false, $viewer)
        ]);
    }

    public function unfollow(User $user)
    {
        $viewer = $this->authenticatedUser();
        if (!$viewer) {
            return $this->unauthorizedResponse();
        }

        if ($viewer->id === $user->id) {
            return response()->json([
                'message' => '自分のフォロー状態は変更できません',
                'data' => null,
            ], 422);
        }

        $viewer->following()->detach($user->id);
        $user->loadCount(['products', 'followers', 'following']);

        return response()->json([
            'message' => 'User unfollowed',
            'data' => $this->formatUserProfileResponse($user, false, $viewer)
        ]);
    }

    // GET /users/me/settings
    public function getSettings()
    {
        // TODO: ユーザー設定の取得処理
        $user = $this->authenticatedUser();
        if (!$user) {
            return $this->unauthorizedResponse();
        }
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
        $user = $this->authenticatedUser();
        if (!$user) {
            return $this->unauthorizedResponse();
        }
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

    public function reviewNotifications(Request $request)
    {
        $user = $this->authenticatedUser();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $limit = (int) $request->query('limit', 50);
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $baseQuery = $this->reviewNotificationsBaseQuery($user);

        $reviews = (clone $baseQuery)
            ->with(['product', 'product.user', 'user'])
            ->limit($limit)
            ->get();

        $readStates = $reviews->isEmpty()
            ? collect()
            : ReviewNotificationRead::query()
                ->where('user_id', $user->id)
                ->whereIn('review_id', $reviews->pluck('id')->all())
                ->get();

        $items = ReviewNotificationPresenter::presentMany($reviews, $readStates);
        $totalCount = (clone $baseQuery)->count();
        $unreadCount = $this->unreadReviewNotificationsCount($user);

        return response()->json([
            'message' => 'Review notifications',
            'data' => $items,
            'meta' => [
                'total' => $totalCount,
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    public function markReviewNotificationsRead(Request $request)
    {
        $user = $this->authenticatedUser();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'reviewIds' => ['required', 'array', 'min:1'],
            'reviewIds.*' => ['integer', 'min:1'],
        ]);

        $reviewIds = collect($validated['reviewIds'])
            ->map(static fn (int $id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($reviewIds->isEmpty()) {
            return $this->notificationUpdateResponse(
                'No notifications updated',
                0,
                $this->unreadReviewNotificationsCount($user)
            );
        }

        $targetReviewIds = $this->reviewNotificationsBaseQuery($user)
            ->whereIn('reviews.id', $reviewIds->all())
            ->pluck('reviews.id');

        if ($targetReviewIds->isEmpty()) {
            return $this->notificationUpdateResponse(
                'No notifications updated',
                0,
                $this->unreadReviewNotificationsCount($user)
            );
        }

        $updated = $this->upsertReviewNotificationReads($user, $targetReviewIds);

        return $this->notificationUpdateResponse(
            'Notifications marked as read',
            $updated,
            $this->unreadReviewNotificationsCount($user)
        );
    }

    public function markAllReviewNotificationsRead()
    {
        $user = $this->authenticatedUser();
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $reviewIds = $this->reviewNotificationsBaseQuery($user)
            ->pluck('reviews.id');

        if ($reviewIds->isEmpty()) {
            return $this->notificationUpdateResponse('No notifications to update', 0, 0);
        }

        $updated = $this->upsertReviewNotificationReads($user, $reviewIds);

        return $this->notificationUpdateResponse('All notifications marked as read', $updated, 0);
    }

    private function reviewNotificationsBaseQuery(User $user): Builder
    {
        return Review::query()
            ->whereHas('product', static function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where(static function (Builder $query) use ($user) {
                $query->whereNull('author_id')
                    ->orWhere('author_id', '<>', $user->id);
            })
            ->orderByDesc('created_at');
    }

    private function unreadReviewNotificationsCount(User $user): int
    {
        return $this->reviewNotificationsBaseQuery($user)
            ->whereDoesntHave('notificationReads', static function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();
    }

    private function upsertReviewNotificationReads(User $user, Collection $reviewIds): int
    {
        $reviewIds = $reviewIds
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($reviewIds->isEmpty()) {
            return 0;
        }

        $now = now();
        $rows = $reviewIds->map(static fn (int $reviewId) => [
            'user_id' => $user->id,
            'review_id' => $reviewId,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        ReviewNotificationRead::upsert($rows, ['user_id', 'review_id'], ['read_at', 'updated_at']);

        return count($rows);
    }

    private function notificationUpdateResponse(string $message, int $updated, int $unreadCount)
    {
        return response()->json([
            'message' => $message,
            'data' => ['updated' => max(0, $updated)],
            'meta' => ['unread_count' => max(0, $unreadCount)],
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

    private function formatUserProfileResponse(User $user, bool $includeEmail = false, ?User $viewer = null): array
    {
        $birthdayValue = $user->birthday;
        if ($birthdayValue instanceof Carbon) {
            $birthdayValue = $birthdayValue->toDateString();
        }

        $joinedAt = $user->created_at instanceof Carbon
            ? $user->created_at->toIso8601String()
            : null;

        $productsCount = (int) ($user->products_count ?? $user->products()->count());
        $followersCount = (int) ($user->followers_count ?? $user->followers()->count());
        $followingCount = (int) ($user->following_count ?? $user->following()->count());

        $isFollowing = null;
        if ($viewer instanceof User && $viewer->id !== $user->id) {
            $isFollowing = $viewer->isFollowing($user);
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'displayName' => $user->display_name,
            'avatarUrl' => $user->avatar_url,
            'headerUrl' => $user->header_url,
            'bio' => $user->bio,
            'location' => $user->location,
            'website' => $user->website,
            'birthday' => $birthdayValue,
            'locale' => $user->locale,
            'theme' => $user->theme,
            'productsCount' => max(0, $productsCount),
            'joinedAt' => $joinedAt,
            'followersCount' => max(0, $followersCount),
            'followingCount' => max(0, $followingCount),
        ];

        if ($isFollowing !== null) {
            $data['isFollowing'] = $isFollowing;
        }

        if ($includeEmail) {
            $data['email'] = $user->email;
        }

        return $data;
    }

    private function authenticatedUser(): ?User
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            $user = Auth::guard('api')->user();
        }

        return $user instanceof User ? $user : null;
    }

    private function unauthorizedResponse()
    {
        return response()->json([
            'message' => 'User not authenticated',
            'data' => null
        ], 401);
    }
}
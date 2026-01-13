<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DirectMessageController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ProductFileController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Health check route
Route::get('/health-check', \App\Http\Controllers\Api\HealthCheckController::class);

// Products routes (public)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{productId}', [ProductController::class, 'show']);
Route::get('/products/{productId}/versions', [ProductController::class, 'versions']);
Route::get('/products/{productId}/status', [ProductController::class, 'status']);
Route::post('/products/{productId}/access', [ProductController::class, 'incrementAccessCount']);
Route::get('/products/{product}/files/tree', [ProductFileController::class, 'tree']);
Route::get('/products/{product}/files/preview', [ProductFileController::class, 'preview']);
Route::post('/products/{product}/files/download-intent', [ProductFileController::class, 'downloadIntent']);
Route::post('/products/{product}/files/readme', [ProductFileController::class, 'upsertReadme'])->middleware('auth:api');

// Categories routes (public)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{categoryId}', [CategoryController::class, 'show']);

// Rankings route
Route::get('/rankings', [RankingController::class, 'index']);

// Reviews routes (public)
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/{reviewId}/responses', [ReviewController::class, 'responses']);

// Auth routes (public)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/signup', [AuthController::class, 'signup']);

// User routes (public)
Route::get('/users/all', [UserController::class, 'allusers']);
Route::get('/users/{user}', [UserController::class, 'show'])
    ->whereNumber('user');

// Authenticated routes
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // メール認証エンドポイント（開発環境では使用しない）
    Route::get('/auth/email/status', [EmailVerificationController::class, 'status']);
    Route::post('/auth/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // 開発環境ではverifiedミドルウェアを無効化
    // Route::middleware('verified')->group(function () {
        // Product management
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{productId}', [ProductController::class, 'update']);
        Route::delete('/products/{productId}', [ProductController::class, 'destroy']);

        // Category management
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{categoryId}', [CategoryController::class, 'update']);
        Route::delete('/categories/{categoryId}', [CategoryController::class, 'destroy']);

        // Reviews
        Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{reviewId}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{reviewId}', [ReviewController::class, 'destroy']);
        Route::post('/reviews/{reviewId}/vote', [ReviewController::class, 'vote']);
        Route::post('/reviews/{reviewId}/responses', [ReviewController::class, 'storeResponse']);

        // User profile & settings
        Route::get('/users/me', [UserController::class, 'profile']);
        Route::put('/users/me', [UserController::class, 'updateProfile']);
        Route::get('/users/me/settings', [UserController::class, 'getSettings']);
        Route::put('/users/me/settings', [UserController::class, 'updateSettings']);
        Route::get('/users/me/history', [UserController::class, 'history']);
        Route::get('/users/me/notifications/reviews', [UserController::class, 'reviewNotifications']);
        Route::post('/users/me/notifications/reviews/read', [UserController::class, 'markReviewNotificationsRead']);
        Route::post('/users/me/notifications/reviews/read-all', [UserController::class, 'markAllReviewNotificationsRead']);
        Route::get('/users/me/products', [ProductController::class, 'myProducts']);

        // Social graph
        Route::post('/users/{user}/follow', [UserController::class, 'follow'])
            ->whereNumber('user');
        Route::delete('/users/{user}/follow', [UserController::class, 'unfollow'])
            ->whereNumber('user');

        // Direct messages
        Route::prefix('dm')->group(function () {
            Route::get('/unread-count', [DirectMessageController::class, 'unreadCount']);
            Route::get('/conversations', [DirectMessageController::class, 'index']);
            Route::post('/conversations', [DirectMessageController::class, 'store']);
            Route::get('/conversations/{conversation}/messages', [DirectMessageController::class, 'messages'])
                ->whereNumber('conversation');
            Route::post('/conversations/{conversation}/messages', [DirectMessageController::class, 'send'])
                ->whereNumber('conversation');
            Route::put('/conversations/{conversation}/messages/{message}', [DirectMessageController::class, 'update'])
                ->whereNumber('conversation')
                ->whereNumber('message');
            Route::delete('/conversations/{conversation}/messages/{message}', [DirectMessageController::class, 'destroy'])
                ->whereNumber('conversation')
                ->whereNumber('message');
        });
    // }); // verifiedミドルウェアの閉じ括弧（開発環境では無効化）
});

Route::get('/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Home route (ランディングページ用)
Route::get('/home', [HomeController::class, 'index']);

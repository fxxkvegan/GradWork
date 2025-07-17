<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\HomeController;

// Health check route
Route::get('/health-check', \App\Http\Controllers\Api\HealthCheckController::class);

// Products routes
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store'])->middleware('auth:api');
Route::get('/products/{productId}', [ProductController::class, 'show']);
Route::put('/products/{productId}', [ProductController::class, 'update'])->middleware('auth:api');
Route::delete('/products/{productId}', [ProductController::class, 'destroy'])->middleware('auth:api');
Route::get('/products/{productId}/versions', [ProductController::class, 'versions']);
Route::get('/products/{productId}/status', [ProductController::class, 'status']);

// Categories routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories', [CategoryController::class, 'store'])->middleware('auth:api');

// Rankings route
Route::get('/rankings', [RankingController::class, 'index']);

// Reviews routes (プロダクトに紐づくレビュー)
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
Route::post('/products/{productId}/reviews', [ReviewController::class, 'store'])->middleware('auth:api');

// Reviews routes (独立したレビュー編集・削除)
Route::put('/reviews/{reviewId}', [ReviewController::class, 'update'])->middleware('auth:api');
Route::delete('/reviews/{reviewId}', [ReviewController::class, 'destroy'])->middleware('auth:api');
Route::post('/reviews/{reviewId}/vote', [ReviewController::class, 'vote'])->middleware('auth:api');
Route::get('/reviews/{reviewId}/responses', [ReviewController::class, 'responses']);
Route::post('/reviews/{reviewId}/responses', [ReviewController::class, 'storeResponse'])->middleware('auth:api');

// Auth routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/signup', [AuthController::class, 'signup']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:api');
// User routes
Route::get('/users', [UserController::class, 'allusers'])->middleware('auth:api');
Route::get('/users/me', [UserController::class, 'profile'])->middleware('auth:api');
Route::put('/users/me', [UserController::class, 'updateProfile'])->middleware('auth:api');
Route::get('/users/me/settings', [UserController::class, 'getSettings'])->middleware('auth:api');
Route::put('/users/me/settings', [UserController::class, 'updateSettings'])->middleware('auth:api');
Route::get('/users/me/history', [UserController::class, 'history'])->middleware('auth:api');

// Home route (ランディングページ用)
Route::get('/home', [HomeController::class, 'index']);


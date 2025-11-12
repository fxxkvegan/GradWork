<?php

namespace App\Http\Controllers\Api;

use App\Models\HealthCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        $status = 'ok';
        $message = null;
        $timestamp = now();

        try {
            // データベース接続確認
            DB::connection()->getPdo();

            // 他のヘルスチェック項目も必要に応じて追加
        } catch (\Exception $e) {
            $status = 'error';
            $message = $e->getMessage();
        }

        // ヘルスチェック結果をDBに記録
        HealthCheck::create([
            'status' => $status,
            'message' => $message,
            'checked_at' => $timestamp,
        ]);

        return response()->json([
            'ok'              => $status === 'ok',
            'status'          => $status,
            'message'         => $message,
            'timestamp'       => $timestamp->toIso8601String(),
            'laravel'         => app()->version(),
            'php'             => PHP_VERSION,
            'last_deploy_at'  => env('LAST_DEPLOY_TIME', 'Unknown'),
        ]);
    }
}

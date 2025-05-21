<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HealthCheckController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status'     => 'ok',
            'timestamp'  => now()->toIso8601String(),
            'laravel'    => app()->version(),
            'php'        => PHP_VERSION,
        ]);
    }
}


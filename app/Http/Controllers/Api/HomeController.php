<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    // GET /home
    public function index()
    {
        // TODO: ランディングページ用の集約データ取得処理
        return response()->json([
            'message' => 'Aggregated home data',
            'data' => [] // 集約データ
        ]);
    }
}
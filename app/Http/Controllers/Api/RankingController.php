<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    // GET /rankings
    public function index(Request $request)
    {
        // TODO: ランキングの取得処理（typeパラメータ等を利用）
        return response()->json([
            'message' => 'Ranking result',
            'data' => [] // ランキング結果データ
        ]);
    }
}
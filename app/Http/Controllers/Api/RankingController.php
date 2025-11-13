<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    // GET /rankings
    public function index(Request $request)
    {
        $rankings = Product::orderBy('rating', 'desc')
                        ->take(5)
                        ->get();
        // ランキングが見つからなかった場合の処理
        if ($rankings->isEmpty()) {
            return response()->json([
                'message' => 'No rankings found',
                'data' => null
            ], 404);
        }
        return response()->json([
            'message' => 'Ranking result',
            'data' => $rankings // ランキング結果データ
        ]);
    }
}
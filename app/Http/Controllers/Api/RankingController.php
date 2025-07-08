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
        return response()->json([
            'message' => 'Ranking result',
            'items' => $rankings // ランキング結果データ
        ]);
    }
}
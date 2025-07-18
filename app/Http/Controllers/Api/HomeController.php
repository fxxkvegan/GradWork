<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Controllers\Api\RankingController;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    // GET /home
    public function index()
    {
        // TODO: ランディングページ用の集約データ取得処理
        $rankingController = new RankingController();
        $topRanked = $rankingController->index(new Request());
        if ($topRanked->isEmpty()) {
            return response()->json([
                'message' => 'No top ranked products found',
                'data' => [null]
            ], 404);
        }
        $trending = $this->getTrendingProducts();
        if ($trending->isEmpty()) {
            return response()->json([
                'message' => 'No trending products found',
                'data' => [null]
            ], 404);
        }
        return response()->json([
            'message' => 'Aggregated home data',
            'data' => [
                'topRanked' => [
                    'items' => $topRanked,
                    'total' => $topRanked->count(),
                ],
                'trending' => [
                    'items' => $trending,
                    'total' => $trending->count(),
                ]
            ]
        ]);
    }
    public function getTrendingProducts()
    {
        $resentWeeks = Carbon::now()->subWeeks(4);
        return Product::with('categories')
            ->where('created_at', '>=', $resentWeeks)
            ->where('rating', '>=', 3.5)
            ->orderByRaw('(rating * 0.4 + (download_count / 1000) * 0.6) DESC')
            ->limit(10)
            ->get();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Presenters\ProductPresenter;
use Carbon\Carbon;

class HomeController extends Controller
{
    // GET /home
    public function index()
    {
        $topRanked = $this->topRankedProducts();
        if (empty($topRanked)) {
            return response()->json([
                'message' => 'No top ranked products found',
                'data' => [null]
            ], 404);
        }
        $trending = $this->trendingProducts();
        if (empty($trending)) {
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
                    'total' => count($topRanked),
                ],
                'trending' => [
                    'items' => $trending,
                    'total' => count($trending),
                ]
            ]
        ]);
    }

    private function topRankedProducts(int $limit = 10): array
    {
        return Product::with(['categories:id,name', 'user'])
            ->orderByDesc('rating')
            ->orderByDesc('access_count')
            ->limit($limit)
            ->get()
            ->map(static function (Product $product) {
                return ProductPresenter::present($product);
            })
            ->all();
    }

    private function trendingProducts(int $limit = 10): array
    {
        $recentWeeks = Carbon::now()->subWeeks(4);

        return Product::with(['categories:id,name', 'user'])
            ->where('created_at', '>=', $recentWeeks)
            ->where('rating', '>=', 3.5)
            ->orderByRaw('(rating * 0.4 + (access_count / 1000) * 0.6) DESC')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(static function (Product $product) {
                return ProductPresenter::present($product);
            })
            ->all();
    }
}

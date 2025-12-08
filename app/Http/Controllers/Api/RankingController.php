<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Presenters\ProductPresenter;
use Illuminate\Http\Request;


class RankingController extends Controller
{
    // GET /rankings
    public function index(Request $request)
    {
        $limit = (int) $request->input('limit', 10);
        $limit = max(1, min(20, $limit));

        $rankings = Product::with(['categories:id,name', 'user'])
            ->orderByDesc('rating')
            ->orderByDesc('access_count')
            ->take($limit)
            ->get()
            ->map(function (Product $product) {
                $categories = $product->categories->map(static function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                    ];
                })->values();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'rating' => (float) $product->rating,
                    'access_count' => (int) $product->access_count,
                    'google_play_url' => $product->google_play_url,
                    'app_store_url' => $product->app_store_url,
                    'web_app_url' => $product->web_app_url,
                    'image_urls' => ProductPresenter::imageUrls($product),
                    'category_ids' => $product->categoryIds,
                    'categories' => $categories,
                    'owner' => ProductPresenter::presentOwner($product->user),
                ];
            })->values();

        return response()->json([
            'message' => $rankings->isEmpty() ? 'No rankings found' : 'Ranking result',
            'items' => $rankings,
            'count' => $rankings->count(),
        ], 200);
    }
}

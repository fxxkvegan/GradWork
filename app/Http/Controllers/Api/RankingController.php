<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUpvote;
use App\Support\Presenters\ProductPresenter;
use Carbon\Carbon;
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
                    'tagline' => $product->tagline,
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

    // GET /rankings/today
    public function today(Request $request)
    {
        $limit = (int) $request->input('limit', 10);
        $limit = max(1, min(20, $limit));

        // TODO: タイムゾーンは後で調整する（現状はサーバーのローカル日付 기준）
        $startOfDay = Carbon::now()->startOfDay();

        $products = Product::with(['categories:id,name', 'user'])
            ->withCount([
                'upvotes as upvote_count',
                'upvotes as upvotes_today_count' => function ($query) use ($startOfDay) {
                    $query->where('created_at', '>=', $startOfDay);
                },
            ])
            ->orderByDesc('upvotes_today_count')
            ->orderByDesc('upvote_count')
            ->orderByDesc('access_count')
            ->take($limit)
            ->get();

        $productIds = $products->pluck('id')->all();
        $user = auth('api')->user();
        $upvotedIds = [];
        if ($user && $productIds) {
            $upvotedIds = ProductUpvote::where('user_id', $user->id)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->all();
        }

        $rankings = $products->map(function (Product $product) use ($upvotedIds) {
            $categories = $product->categories->map(static function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            })->values();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'tagline' => $product->tagline,
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
                'upvote_count' => (int) ($product->upvote_count ?? 0),
                'upvotes_today_count' => (int) ($product->upvotes_today_count ?? 0),
                'has_upvoted' => in_array($product->id, $upvotedIds, true),
            ];
        })->values();

        return response()->json([
            'message' => $rankings->isEmpty() ? 'No rankings found' : 'Ranking result',
            'items' => $rankings,
            'count' => $rankings->count(),
        ], 200);
    }
}

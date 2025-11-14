<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

use function array_filter;
use function array_values;
use function is_array;
use function is_string;
use function json_decode;
use function json_last_error;


class RankingController extends Controller
{
    // GET /rankings
    public function index(Request $request)
    {
        $limit = (int) $request->input('limit', 10);
        $limit = max(1, min(20, $limit));

        $rankings = Product::with('categories:id,name')
            ->orderByDesc('rating')
            ->orderByDesc('download_count')
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
                    'download_count' => (int) $product->download_count,
                    'image_urls' => $this->normalizeImageUrls($product->getRawOriginal('image_url')),
                    'category_ids' => $product->categoryIds,
                    'categories' => $categories,
                ];
            })->values();

        return response()->json([
            'message' => $rankings->isEmpty() ? 'No rankings found' : 'Ranking result',
            'items' => $rankings,
            'count' => $rankings->count(),
        ], 200);
    }

    private function normalizeImageUrls(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, static function ($url) {
                    return is_string($url) && $url !== '';
                }));
            }

            if (is_string($decoded) && $decoded !== '') {
                return [$decoded];
            }
        }

        return is_string($raw) ? [$raw] : [];
    }
}
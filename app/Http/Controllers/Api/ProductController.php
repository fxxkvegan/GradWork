<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\ProductUpvote;
use App\Models\Version;
use App\Support\Presenters\ProductPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // GET /products
    public function index(Request $request)
    {
        // クエリパラメータから検索条件を取得
        $request->validate([
            'q' => 'nullable|string|max:255',
            'categoryIds' => 'nullable|array',
            'categoryIds.*' => 'integer|exists:categories,id',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string|in:name,rating,access_count,created_at',
        ]);

        $productsQuery = Product::with(['categories', 'user'])
            ->withCount(['upvotes as upvote_count']);

        if ($request->filled('q')) {
            $productsQuery->where('name', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('categoryIds')) {
            $productsQuery->whereHas('categories', function ($query) use ($request) {
                $query->whereIn('categories.id', $request->categoryIds);
            });
        }

        switch ($request->sort) {
            case 'name':
                $productsQuery->orderBy('name');
                break;
            case 'rating':
                $productsQuery->orderBy('rating', 'desc');
                break;
            case 'access_count':
                $productsQuery->orderBy('access_count', 'desc');
                break;
            case 'created_at':
                $productsQuery->orderBy('created_at', 'desc');
                break;
            default:
                $productsQuery->orderBy('created_at', 'desc');
        }

        $limit = $request->input('limit', 10);
        $products = $productsQuery->paginate($limit);

        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'No products found',
                'items' => [],
                'total' => 0,
                'currentPage' => 1,
                'lastPage' => 1,
                'perPage' => $limit,
            ], 200);
        }

        $itemsSource = $products->items();
        $productIds = array_map(static fn (Product $product) => $product->id, $itemsSource);
        $user = auth('api')->user();
        $upvotedIds = [];
        if ($user && $productIds) {
            $upvotedIds = ProductUpvote::where('user_id', $user->id)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->all();
        }

        $items = array_map(static function (Product $product) use ($upvotedIds) {
            $payload = ProductPresenter::present($product);
            $payload['has_upvoted'] = in_array($product->id, $upvotedIds, true);
            $payload['upvote_count'] = (int) ($payload['upvote_count'] ?? 0);
            return $payload;
        }, $itemsSource);

        return response()->json([
            'items' => $items,
            'total' => $products->total(),
            'currentPage' => $products->currentPage(),
            'lastPage' => $products->lastPage(),
            'perPage' => $products->perPage(),
            'nextPageUrl' => $products->nextPageUrl(),
            'prevPageUrl' => $products->previousPageUrl(),
            'message' => 'Product list retrieved successfully'
        ]);
    }

    // POST /products
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:60',
            'description' => 'nullable|string',
            'categoryIds' => 'nullable|array',
            'categoryIds.*' => 'integer|exists:categories,id',
            'image_url' => 'nullable|array|max:5',
            'image_url.*' => 'file|mimes:jpg,jpeg,png,gif',
            'remove_image_urls' => 'nullable|array',
            'remove_image_urls.*' => 'string',
            'google_play_url' => 'nullable|url|max:2048',
            'app_store_url' => 'nullable|url|max:2048',
            'web_app_url' => 'nullable|url|max:2048',
        ]);

        $imageUrls = [];

        if ($request->hasFile('image_url')) {
            foreach ($request->file('image_url') as $file) {
                $path = $file->store('products', 'public');
                if (!is_string($path) || $path === '') {
                    continue;
                }
                $url = Storage::url($path);
                $imageUrls[] = $url;
            }
        }

        $resolvedTagline = $this->resolveTagline(
            $request->input('tagline'),
            $request->input('description')
        );

        $product = Product::create([
            'name' => $request->name,
            'tagline' => $resolvedTagline,
            'description' => $request->description,
            'rating' => 0,
            'access_count' => 0,
            'google_play_url' => $request->google_play_url,
            'app_store_url' => $request->app_store_url,
            'web_app_url' => $request->web_app_url,
            'image_url' => $imageUrls ? json_encode($imageUrls) : null,
            'user_id' => $request->user()->id,
        ]);

        if ($request->filled('categoryIds')) {
            $product->categories()->attach($request->categoryIds);
        }

        $product->load(['categories', 'user']);

        return response()->json(ProductPresenter::present($product), 201);
    }

    // GET /products/{productId}
    public function show($productId)
    {
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID'
            ], 400);
        }

        $product = Product::with(['categories', 'user'])
            ->withCount(['upvotes as upvote_count'])
            ->findOrFail($productId);

        $user = auth('api')->user();
        $hasUpvoted = false;
        if ($user) {
            $hasUpvoted = ProductUpvote::where('product_id', $product->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        $payload = ProductPresenter::present($product);
        $payload['has_upvoted'] = $hasUpvoted;
        $payload['upvote_count'] = (int) ($payload['upvote_count'] ?? 0);

        return response()->json($payload);
    }

    // PUT /products/{productId}
    public function update(Request $request, $productId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:60',
            'description' => 'nullable|string',
            'categoryIds' => 'nullable|array',
            'categoryIds.*' => 'integer|exists:categories,id',
            'image_url' => 'nullable|array|max:5',
            'image_url.*' => 'file|mimes:jpg,jpeg,png,gif',
            'remove_image_urls' => 'nullable|array',
            'remove_image_urls.*' => 'string',
            'google_play_url' => 'nullable|url|max:2048',
            'app_store_url' => 'nullable|url|max:2048',
            'web_app_url' => 'nullable|url|max:2048',
        ]);

        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID'
            ], 400);
        }

        $product = Product::findOrFail($productId);

        $existingImages = Product::decodeImageUrls($product->getRawOriginal('image_url'));
        $removeTargets = $request->input('remove_image_urls', []);
        $normalizedRemovals = array_values(array_filter(array_map(function ($value) {
            if (!is_string($value) || $value === '') {
                return null;
            }

            $path = parse_url($value, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                return $path;
            }

            return $value;
        }, is_array($removeTargets) ? $removeTargets : [])));
        $removeSet = array_flip($normalizedRemovals);

        $remainingImages = array_values(array_filter($existingImages, function ($url) use ($removeSet) {
            return !array_key_exists($url, $removeSet);
        }));

        // 明示的に削除された画像ファイルをストレージから削除
        foreach ($existingImages as $url) {
            if (!array_key_exists($url, $removeSet)) {
                continue;
            }

            $path = parse_url($url, PHP_URL_PATH) ?: $url;
            if (is_string($path)) {
                $storagePath = str_replace('/storage/', '', $path);
                Storage::disk('public')->delete($storagePath);
            }
        }

        $newImages = [];
        if ($request->hasFile('image_url')) {
            foreach ($request->file('image_url') as $file) {
                $path = $file->store('products', 'public');
                if (!is_string($path) || $path === '') {
                    continue;
                }
                $url = Storage::url($path);
                $newImages[] = $url;
            }
        }

        if (count($remainingImages) + count($newImages) > 5) {
            return response()->json([
                'message' => '画像は最大5枚までアップロードできます',
            ], 422);
        }

        $imageUrls = array_merge($remainingImages, $newImages);

        $resolvedTagline = $this->resolveTagline(
            $request->input('tagline'),
            $request->input('description')
        );

        $updateData = [
            'name' => $request->name,
            'tagline' => $resolvedTagline,
            'description' => $request->description,
            'image_url' => json_encode($imageUrls),
        ];

        if ($request->has('google_play_url')) {
            $updateData['google_play_url'] = $request->google_play_url;
        }
        if ($request->has('app_store_url')) {
            $updateData['app_store_url'] = $request->app_store_url;
        }
        if ($request->has('web_app_url')) {
            $updateData['web_app_url'] = $request->web_app_url;
        }

        $product->update($updateData);

        if ($request->has('categoryIds')) {
            $product->categories()->sync($request->categoryIds);
        }

        $product->load(['categories', 'user']);

        return response()->json(ProductPresenter::present($product));
    }

    private function resolveTagline(?string $tagline, ?string $description): string
    {
        $tagline = trim((string) $tagline);
        if ($tagline !== '') {
            return Str::limit($tagline, 60, '');
        }

        $description = trim((string) $description);
        if ($description !== '') {
            return Str::limit($description, 60, '');
        }

        return '（説明未設定）';
    }

    // DELETE /products/{productId}
    public function destroy(Request $request, $productId)
    {
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $productId
            ], 400);
        }

        $product = Product::findOrFail($productId);

        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'この作品を削除する権限がありません',
            ], 403);
        }

        $imageUrls = Product::decodeImageUrls($product->getRawOriginal('image_url'));
        foreach ($imageUrls as $url) {
            $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));
            Storage::disk('public')->delete($path);
        }

        $product->delete();

        return response()->json(null, 204);
    }

    // GET /products/{productId}/versions
    public function versions($productId)
    {
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $productId
            ], 400);
        }

        $response = Version::where('product_id', $productId)->get();
        return response()->json([
            'message' => 'List of versions',
            'data' => $response
        ]);
    }

    // GET /products/{productId}/status
    public function status($productId)
    {
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $productId
            ], 400);
        }

        $response = ProductStatus::where('product_id', $productId)->firstOrFail();
        return response()->json([
            'message' => 'Product status',
            'data' => $response
        ]);
    }

    // GET /my-products
    public function myProducts(Request $request)
    {
        $user = $request->user();

        $products = Product::with(['categories', 'user'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(static function (Product $product) {
                return ProductPresenter::present($product);
            });

        return response()->json([
            'items' => $products,
            'count' => $products->count(),
        ]);
    }

    /**
     * POST /products/{productId}/access
     * アクセスカウントをインクリメントする
     */
    public function incrementAccessCount($productId)
    {
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID'
            ], 400);
        }

        $product = Product::findOrFail($productId);

        if (!$product->hasExternalLinks()) {
            return response()->json([
                'message' => 'This product has no external links',
            ], 400);
        }

        $product->increment('access_count');

        return response()->json([
            'message' => 'Access count incremented',
            'access_count' => $product->access_count,
        ]);
    }
}

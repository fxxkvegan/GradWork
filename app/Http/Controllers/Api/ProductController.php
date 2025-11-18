<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * 相対パスの配列を完全なURLの配列に変換するヘルパーメソッド
     * 
     * @param array $relativePaths 相対パスの配列 ["/storage/products/xxx.jpg"]
     * @return array 完全なURLの配列 ["https://app.nice-dig.com/storage/products/xxx.jpg"]
     */
    private function convertToFullUrls(array $relativePaths): array
    {
        return array_map(function ($path) {
            // すでに完全なURLの場合はそのまま返す
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            // 相対パスを完全なURLに変換
            return url($path);
        }, $relativePaths);
    }

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
            'sort' => 'nullable|string|in:name,rating,download_count,created_at',
        ]);

        $productsQuery = Product::with('categories');

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
            case 'download_count':
                $productsQuery->orderBy('download_count', 'desc');
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

        $items = array_map(function ($product) {
            $productArray = $product->toArray();
            // ✅ JSONデコード → 完全なURLに変換
            $decodedImages = Product::decodeImageUrls($product->getRawOriginal('image_url'));
            $productArray['image_url'] = $this->convertToFullUrls($decodedImages);
            return $productArray;
        }, $products->items());

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
            'description' => 'nullable|string',
            'categoryIds' => 'nullable|array',
            'categoryIds.*' => 'integer|exists:categories,id',
            'image_url' => 'nullable|array|max:5',
            'image_url.*' => 'file|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $imageUrls = [];

        if ($request->hasFile('image_url')) {
            foreach ($request->file('image_url') as $file) {
                $path = $file->store('products', 'public');
                $url = Storage::url($path);
                $imageUrls[] = $url;
            }
        }

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'rating' => 0,
            'download_count' => 0,
            'image_url' => $imageUrls ? json_encode($imageUrls) : null,
            'user_id' => $request->user()->id,
        ]);

        if ($request->filled('categoryIds')) {
            $product->categories()->attach($request->categoryIds);
        }

        $product->load('categories');

        $productArray = $product->toArray();
        // ✅ JSONデコード → 完全なURLに変換
        $decodedImages = Product::decodeImageUrls($product->getRawOriginal('image_url'));
        $productArray['image_url'] = $this->convertToFullUrls($decodedImages);

        return response()->json($productArray, 201);
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

        $product = Product::with('categories')->findOrFail($productId);

        $productArray = $product->toArray();
        // ✅ JSONデコード → 完全なURLに変換
        $decodedImages = Product::decodeImageUrls($product->getRawOriginal('image_url'));
        $productArray['image_url'] = $this->convertToFullUrls($decodedImages);
        
        return response()->json($productArray);
    }

    // PUT /products/{productId}
    public function update(Request $request, $productId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categoryIds' => 'nullable|array',
            'categoryIds.*' => 'integer|exists:categories,id',
            'image_url' => 'nullable|array|max:5',
            'image_url.*' => 'file|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID'
            ], 400);
        }

        $product = Product::findOrFail($productId);

        // 新しい画像がアップロードされた場合の処理
        $imageUrls = [];
        if ($request->hasFile('image_url')) {
            // 古い画像を削除
            $oldImages = Product::decodeImageUrls($product->getRawOriginal('image_url'));
            foreach ($oldImages as $oldUrl) {
                $oldPath = str_replace('/storage/', '', parse_url($oldUrl, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            // 新しい画像を保存
            foreach ($request->file('image_url') as $file) {
                $path = $file->store('products', 'public');
                $url = Storage::url($path);
                $imageUrls[] = $url;
            }
        } else {
            // 画像の変更がない場合は既存の画像URLを保持
            $imageUrls = Product::decodeImageUrls($product->getRawOriginal('image_url'));
        }

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'image_url' => json_encode($imageUrls),
        ]);

        if ($request->has('categoryIds')) {
            $product->categories()->sync($request->categoryIds);
        }

        $product->load('categories');

        $productArray = $product->toArray();
        // ✅ JSONデコード → 完全なURLに変換
        $decodedImages = Product::decodeImageUrls($product->getRawOriginal('image_url'));
        $productArray['image_url'] = $this->convertToFullUrls($decodedImages);

        return response()->json($productArray);
    }

    // DELETE /products/{productId}
    public function destroy($productId)
    {
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $productId
            ], 400);
        }

        $product = Product::findOrFail($productId);

        // 画像ファイルを削除
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

        $products = Product::with('categories')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Product $product) {
                $productArray = $product->toArray();
                // ✅ JSONデコード → 完全なURLに変換
                $decodedImages = Product::decodeImageUrls($product->getRawOriginal('image_url'));
                $productArray['image_url'] = $this->convertToFullUrls($decodedImages);
                return $productArray;
            });

        return response()->json([
            'items' => $products,
            'count' => $products->count(),
        ]);
    }
}

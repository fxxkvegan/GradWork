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
    // GET /products
    public function index(Request $request)
    {
        // クエリパラメータから検索条件を取得
        $request->validate([
            'q' => 'nullable|string|max:255', // 検索クエリのバリデーション
            'categoryIds' => 'nullable|array', // カテゴリIDの配列
            'categoryIds.*' => 'integer|exists:categories,id', // 各カテゴリIDのバリデーション
            'page' => 'nullable|integer|min:1', // ページ番号のバリデーション
            'limit' => 'nullable|integer|min:1|max:100', // 1ページあたりの件数のバリデーション
            'sort' => 'nullable|string|in:name,rating,download_count,created_at', // ソート条件のバリデーション
        ]);

        $productsQuery = Product::with('categories');

        if ($request->filled('q')) {
            // 製品名で検索
            $productsQuery->where('name', 'like', '%' . $request->q . '%');
        }

        // カテゴリフィルター
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
                $productsQuery->orderBy('created_at', 'desc'); // デフォルトは作成日時でソート
        }
        // ページネーションの設定
        $limit = $request->input('limit', 10); // デフォルトは10でとりあえず
        $products = $productsQuery->paginate($limit);
        // 製品が見つからなかった場合の処理
        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'No products found',
                'items' => null
            ], 200);
        }

        $items = array_map(function ($product) {
            $productArray = $product->toArray();
            $productArray['image_url'] = Product::decodeImageUrls($product->getRawOriginal('image_url'));
            return $productArray;
        }, $products->items());

        return response()->json([
            'items' => $items, // 製品データの配列
            'total' => $products->total(), // 総件数
            'currentPage' => $products->currentPage(), // 現在のページ番号
            'lastPage' => $products->lastPage(), // 最終ページ番号
            'perPage' => $products->perPage(), // 1ページあたりの件数
            'nextPageUrl' => $products->nextPageUrl(), // 次のページのURL
            'prevPageUrl' => $products->previousPageUrl(), // 前のページのURL
            'message' => 'Product list retrieved successfully'
        ]);
    }

    // POST /products
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categoryIds' => 'nullable|array', // カテゴリIDの配列
            'categoryIds.*' => 'integer|exists:categories,id', // 各カテゴリIDのバリデーション
            'image_url' => 'nullable|array|max:5', //とりあえず五枚まで
            'image_url.*' => 'file|mimes:jpg,jpeg,png,gif|max:2048', // 画像のバリデーション
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
            'rating' => 0,          //初期値
            'download_count' => 0,  //初期値
            'image_url' => $imageUrls ? json_encode($imageUrls) : null, // 画像URLの保存
            'user_id' => $request->user()->id,
        ]);

        // カテゴリの関連付け
        if ($request->filled('categoryIds')) {
            $product->categories()->attach($request->categoryIds);
        }

        // カテゴリ情報を含めて返す
        $product->load('categories');

        $productArray = $product->toArray();
        $productArray['image_url'] = Product::decodeImageUrls($product->getRawOriginal('image_url'));

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
        $decodedImages = Product::decodeImageUrls($product->getRawOriginal('image_url'));      
        $productArray['image_url'] = array_map(fn ($image) => public_path($image), $decodedImages);
        return response()->json($productArray);
        }

        // PUT /products/{productId}
        public function update(Request $request, $productId)
        {
        // TODO: 製品情報の更新処理
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categoryIds' => 'nullable|array', // カテゴリIDの配列
            'categoryIds.*' => 'integer|exists:categories,id', // 各カテゴリIDのバリデーション
            'image_url' => 'nullable|array|max:5', //とりあえず五枚まで
            'image_url.*' => 'file|mimes:jpg,jpeg,png,gif|max:2048', // 画像のバリデーション
        ]);

        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID'
            ], 400);
        }

        $product = Product::findOrFail($productId);
        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'rating' => $request->rating,
            'download_count' => $request->download_count,
            'image_url' => $request->image_url ? Storage::url($request->image_url) : $product->image_url, // 画像URLの更新
        ]);
        if ($request->hasFile('image_url')) {
            Storage::putFileAs(
                'public/products', // ストレージのパス
                $request->file('image_url'), // アップロードされたファイル
                $product->id . '.' . $request->file('image_url')->getClientOriginalExtension() || null,
            );
        }
        // カテゴリの更新
        if ($request->has('categoryIds')) {
            $product->categories()->sync($request->categoryIds);
        }

        // カテゴリ情報を含めて返す
        $product->load('categories');

        $productArray = $product->toArray();
        $productArray['image_url'] = Product::decodeImageUrls($product->getRawOriginal('image_url'));

        return response()->json($productArray);
    }

    // DELETE /products/{productId}
    public function destroy($productId)
    {
        //製品の削除
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $productId
            ], 400);
        }
        $product = Product::findOrFail($productId);
        $product->delete();

        return response()->json(null, 204);
    }

    // GET /products/{productId}/versions
    public function versions($productId)
    {
        // TODO: 製品のバージョン履歴を取得する処理
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
            'data' => $response // バージョン情報の配列
        ]);
    }

    // GET /products/{productId}/status
    public function status($productId)
    {
        // TODO: 製品の状態（online, maintenance, deprecated等）の取得処理
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
            'data' => $response // 状態情報
        ]);
    }

    public function myProducts(Request $request)
    {
        $user = $request->user();

        $products = Product::with('categories')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Product $product) {
                $productArray = $product->toArray();
                $productArray['image_url'] = Product::decodeImageUrls($product->getRawOriginal('image_url'));
                return $productArray;
            });

        return response()->json([
            'items' => $products,
            'count' => $products->count(),
        ]);
    }
}

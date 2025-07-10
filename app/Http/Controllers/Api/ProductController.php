<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\Version;
use Illuminate\Http\Request;

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
        
        if($request->filled('q')) {
            // 製品名で検索
            $productsQuery->where('name', 'like', '%' . $request->q . '%');
        }

        // カテゴリフィルター
        if($request->filled('categoryIds')) {
            $productsQuery->whereHas('categories', function($query) use ($request) {
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
        if($products->isEmpty()) {
            return response()->json([
                'message' => 'No products found',
                'items' => null
            ], 200);
        }

        return response()->json([
            'message' => 'List of products',
            'items' => $products->items(), // 製品データの配列
            'total' => $products->total(), // 総件数
            'current_page' => $products->currentPage(), // 現在のページ番号
            'last_page' => $products->lastPage(), // 最終ページ番号
            'per_page' => $products->perPage(), // 1ページあたりの件数
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
            'rating' => 'nullable|numeric|min:0|max:5',
            'download_count' => 'nullable|integer|min:0',
        ]);
        
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'rating' => $request->rating,
            'download_count' => $request->download_count,
        ]);

        // カテゴリの関連付け
        if ($request->filled('categoryIds')) {
            $product->categories()->attach($request->categoryIds);
        }

        // カテゴリ情報も含めて返す
        $product->load('categories');
        
        // 製品情報が正常でなかった場合
        if (!$product) {
            return response()->json([
                'message' => 'Failed to create product',
                'data' => null
            ], 500);
        }
        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product // 作成された製品情報
        ], 201);
    }

    // GET /products/{productId}
    public function show($productId)
    {
        $productId = intval($productId);
        if ($productId <= 0) {    
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $productId
            ], 400);
        }
        
        $product = Product::with('categories')->findOrFail($productId);
        
        return response()->json([
            'message' => 'Product details',
            'data' => $product  // 製品詳細データ（categoryIdsも含む）
        ]);
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
            'rating' => 'nullable|numeric|min:0|max:5',
            'download_count' => 'nullable|integer|min:0',
        ]);
        
        $productId = intval($productId);
        if ($productId <= 0) {    
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $productId
            ], 400);
        }
        
        $product = Product::findOrFail($productId);
        $product->name = $request->name;
        $product->description = $request->description;
        $product->rating = $request->rating;
        $product->download_count = $request->download_count;
        $product->save();

        // カテゴリの更新
        if ($request->has('categoryIds')) {
            $product->categories()->sync($request->categoryIds);
        }

        // カテゴリ情報も含めて返す
        $product->load('categories');

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product // 更新後の製品情報
        ]);
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
        $response = Version::where('product_id',$productId)->get();
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
        $response = ProductStatus::where('product_id',$productId)->firstOrFail();
        return response()->json([
            'message' => 'Product status',
            'data' => $response // 状態情報
        ]);
    }
}
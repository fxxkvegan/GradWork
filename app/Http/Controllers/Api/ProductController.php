<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isEmpty;

class ProductController extends Controller
{
    // GET /products
    public function index(Request $request)
    {
        // クエリパラメータから検索条件を取得
        $request->validate([
            'q' => 'nullable|string|max:255', // 検索クエリのバリデーション
            'page' => 'nullable|integer|min:1', // ページ番号のバリデーション
            'limit' => 'nullable|integer|min:1|max:100', // 1ページあたりの件数のバリデーション
            'sort' => 'nullable|string|in:name,rating,download_count,created_at', // ソート条件のバリデーション
        ]);
        $query = Product::query();
        if($request->filled('q')) {
            // 製品名で検索
            $query->where('name', 'like', '%' . $request->q . '%');
        }
        switch ($request->sort) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'download_count':
                $query->orderBy('download_count', 'desc');
                break;
            case 'created_at':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc'); // デフォルトは作成日時でソート
        }
        // ページネーションの設定
        $limit = $request->input('limit', 10); // デフォルトは10でとりあえず
        $products = $query->paginate($limit);
        // 製品が見つからなかった場合の処理
        if(isEmpty($products)) {
            return response()->json([
                'message' => 'No products found',
                'items' => null
            ], 404);
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
            'rating' => 'nullable|numeric|min:0|max:5',
            'download_count' => 'nullable|integer|min:0',
        ]);
        $product = Product::create([
            'id' => $request->id, // IDは自動生成されるため通常は不要
            'name' => $request->name,
            'description' => $request->description,
            'rating' => $request->rating,
            'download_count' => $request->download_count,
            'created_at' => now(), // 作成日時
            'updated_at' => now(), // 更新日時
        ]);
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
        $product = Product::findOrFail($productId);
        return response()->json([
            'message' => 'Product details',
            'data' => $product  // 製品詳細データ
        ]);
    }

    // PUT /products/{productId}
    public function update(Request $request, $productId)
    {
        // TODO: 製品情報の更新処理
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
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
        $product->updated_at = now();

        $product->save();

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
        $product = Product::findOrFail($productId);
        $product->delete();
    
        return response()->json(null, 204);
    }

    // GET /products/{productId}/versions
    public function versions($productId)
    {
        // TODO: 製品のバージョン履歴を取得する処理
        return response()->json([
            'message' => 'List of versions',
            'data' => [] // バージョン情報の配列
        ]);
    }

    // GET /products/{productId}/status
    public function status($productId)
    {
        // TODO: 製品の状態（online, maintenance, deprecated等）の取得処理
        return response()->json([
            'message' => 'Product status',
            'data' => [] // 状態情報
        ]);
    }
}
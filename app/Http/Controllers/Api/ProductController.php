<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // GET /products
    public function index(Request $request)
    {
        // TODO: 製品一覧の取得とフィルタリング、ページネーションの実装
        $filters = $request->only(['page', 'limit', 'q','sort']);
        // ここで製品データを取得するロジックを実装
        $products = Product::filter($filters);
        // 例: $products = Product::filter($filters)->paginate(
        return response()->json([
            'message' => 'List of products',
            'data' => $products // 製品データの配列
        ]);
    }

    // POST /products
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'raiting' => 'nullable|numeric|min:0|max:5',
            'download_count' => 'nullable|integer|min:0',
        ]);
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'raiting' => $request->raiting,
            'download_count' => $request->download_count,
        ]);
        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product // 作成された製品情報
        ], 201);
    }

    // GET /products/{productId}
    public function show($productId)
    {
        $productId = intval($productId);
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
        return response()->json([
            'message' => 'Product updated successfully',
            'data' => [] // 更新後の製品情報
        ]);
    }

    // DELETE /products/{productId}
    public function destroy($productId)
    {
        // TODO: 製品の削除処理
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
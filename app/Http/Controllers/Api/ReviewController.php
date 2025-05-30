<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // GET /products/{productId}/reviews
    public function index($productId)
    {
        // TODO: 製品に紐づくレビュー一覧の取得
        return response()->json([
            'message' => 'List of reviews',
            'data' => [] // レビュー一覧データ
        ]);
    }

    // POST /products/{productId}/reviews
    public function store(Request $request, $productId)
    {
        // TODO: 製品に対する新規レビューの投稿処理
        return response()->json([
            'message' => 'Review created successfully',
            'data' => [] // 作成されたレビュー情報
        ], 201);
    }

    // PUT /reviews/{reviewId}
    public function update(Request $request, $reviewId)
    {
        // TODO: 指定レビューの更新処理
        return response()->json([
            'message' => 'Review updated successfully',
            'data' => [] // 更新後のレビュー情報
        ]);
    }

    // DELETE /reviews/{reviewId}
    public function destroy($reviewId)
    {
        // TODO: 指定レビューの削除処理
        return response()->json(null, 204);
    }

    // POST /reviews/{reviewId}/vote
    public function vote($reviewId)
    {
        // TODO: レビューへの「いいね」投票処理
        return response()->json(null, 204);
    }

    // GET /reviews/{reviewId}/responses
    public function responses($reviewId)
    {
        // TODO: レビューへのレスポンス一覧取得処理
        return response()->json([
            'message' => 'List of review responses',
            'data' => [] // レスポンス一覧データ
        ]);
    }

    // POST /reviews/{reviewId}/responses
    public function storeResponse(Request $request, $reviewId)
    {
        // TODO: レビューへの新規レスポンス投稿処理
        return response()->json([
            'message' => 'Review response created',
            'data' => [] // 作成されたレスポンスデータ
        ], 201);
    }
}
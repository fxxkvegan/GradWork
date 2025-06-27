<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ReviewController
 *
 * 製品レビューに関するAPIコントローラー
 */

class ReviewController extends Controller
{
    // GET /products/{productId}/reviews
    public function index($productId)
    {
        // TODO: 製品に紐づくレビュー一覧の取得
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => []
            ], 400);
        }
        // レビュー一覧を取得
        $reviews = Review::where('product_id', $productId)->get();
        return response()->json([
            'message' => 'List of reviews',
            'data' => $reviews // レビュー一覧データ
        ]);
    }

    // POST /products/{productId}/reviews
    public function store(Request $request, $productId)
    {
        // TODO: 製品に対する新規レビューの投稿処理
        $productId = intval($productId);
        if ($productId <= 0) {    
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => []
            ], 400);
        }
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);
        $review = new Review();
        $review->product_id = $productId;
        $review->author_id = auth()->id(); // 認証済みユーザーのIDを使用
        $review->title = $validatedData['title'];
        $review->body = $validatedData['body'];
        $review->rating = $validatedData['rating'];
        $review->helpful_count = 0; // 初期値は0
        $review->save();
        // レスポンスデータの整形
        $responseData = [
            'id' => $review->id,
            'product_id' => $review->product_id,
            'title' => $review->title,
            'body' => $review->body,
            'rating' => $review->rating,
            'helpful_count' => $review->helpful_count,
            'created_at' => $review->created_at,
        ];
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
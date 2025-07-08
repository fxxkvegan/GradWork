<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Response;
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
                'data' => $productId
            ], 400);
        }
        // レビュー一覧を取得
        $reviews = Review::where('product_id', $productId)->get();
        // レビューが存在しない場合の処理
        if ($reviews->isEmpty()) {  
            return response()->json([
                'message' => 'reviews not found for this product',
                'data' => $productId
            ], 404);
        }
        // レスポンスデータの整形
        $reviews = $reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'product_id' => $review->product_id,
                'author_id' => $review->author_id,
                'title' => $review->title,
                'body' => $review->body,
                'rating' => $review->rating,
                'helpful_count' => $review->helpful_count,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ];
        });
        return response()->json([
            'message' => 'List of reviews',
            'items' => $reviews // レビュー一覧データ
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
                'data' => $productId
            ], 400);
        }
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);
        $review = new Review();
        $review->product_id = $productId;
        $review->author_id = Auth::id(); // 認証済みユーザーのIDを使用
        $review->title = $validatedData['title'];
        $review->body = $validatedData['body'];
        $review->rating = $validatedData['rating'];
        $review->helpful_count = 0; // 初期値は0
        $review->created_at = now(); // 作成日時
        $review->updated_at = now(); // 更新日時
        $review->save();
        // レスポンスデータの整形
        $responseData = [
            'id' => $review->id,
            'product_id' => $review->product_id,
            'title' => $review->title,
            'body' => $review->body,
            'rating' => $review->rating,
            'helpful_count' => $review->helpful_count,
            'created_at' => $review->created_at ,
        ];
        return response()->json([
            'message' => 'Review list',
            'data' => $responseData // 作成されたレビュー情報
        ], 201);
    }

    // PUT /reviews/{reviewId}
    public function update(Request $request, $reviewId)
    {
        $reviewId = intval($reviewId);
        if ($reviewId <= 0) {    
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $reviewId
            ], 400);
        }
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);
        $review = Review::findOrFail($reviewId);
        // レビューの更新処理
        $review->title = $validatedData['title'];
        $review->body = $validatedData['body'];
        $review->rating = $validatedData['rating'];
        $review->updated_at = now(); // 更新日時
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
            'updated_at' => $review->updated_at,
        ];

        return response()->json([
            'message' => 'Review updated successfully',
            'data' => $responseData // 更新後のレビュー情報
        ]);
    }

    // DELETE /reviews/{reviewId}
    public function destroy($reviewId)
    {
        //rviewIdを検証する
        $reviewId = intval($reviewId);
        if ($reviewId <= 0) {
            return response()->json([
                'message' => 'Invalid review ID',
                'data' => $reviewId
            ], 400);
        }
        // 指定レビューの削除処理
        $review = Review::findOrFail($reviewId);
        $review->delete();
    
        return response()->json(null, 204);
    }

    // POST /reviews/{reviewId}/vote
    public function vote($reviewId)
    {
        // TODO: レビューへの「いいね」投票処理
        $reviewId = intval($reviewId);
        if ($reviewId <= 0) {
            return response()->json([
                'message' => 'Invalid review ID',
                'data' => $reviewId
            ], 400);
        }
        $review = Review::findOrFail($reviewId);
        $review->helpful_count += 1; // いいね数を増やす
        $review->save();
        return response()->json(null, 204);
    }

    // GET /reviews/{reviewId}/responses
    public function responses($reviewId)
    {
        // TODO: レビューへのレスポンス一覧取得処理
        $reviewId = intval($reviewId);
        if ($reviewId <= 0) {
            return response()->json([
                'message' => 'Invalid review ID',
                'data' => $reviewId
            ], 400);
        }
        $review = Review::findOrFail($reviewId);
        $reviewResponses = $review->responses; // レビューに紐づくレスポンスを取得
        // レスポンスが存在しない場合の処理
        if ($reviewResponses->isEmpty()) {
            return response()->json([
                'message' => 'No responses found for this review',
                'data' => $reviewId
            ], 404);
        }
        // レスポンスデータの整形
        $reviewResponses = $reviewResponses->map(function ($response) {
            return [
                'id' => $response->id,
                'review_id' => $response->review_id,
                'author_id' => $response->author_id,
                'body' => $response->body,
                'created_at' => $response->created_at,
                'updated_at' => $response->updated_at,
            ];
        });
        return response()->json([
            'message' => 'List of review responses',
            'data' => $reviewResponses // レスポンス一覧データ
        ]);
    }

    // POST /reviews/{reviewId}/responses
    public function storeResponse(Request $request, $reviewId)
    {
        // TODO: レビューへの新規レスポンス投稿処理
        $reviewId = intval($reviewId);
        if ($reviewId <= 0) { 
            return response()->json([
                'message' => 'Invalid review ID',
                'data' => $reviewId
            ], 400);
        }
        $validatedData = $request->validate([
            'body' => 'required|string',
        ]);
        $review = Review::findOrFail($reviewId);
        $responseData = Response::create([
            'review_id' => $reviewId,
            'author_id' => Auth::id(), // 認証済みユーザーのIDを使用
            'body' => $validatedData['body'],
            'created_at' => now(), // 作成日時
            'updated_at' => now(), // 更新日時
        ]);
        // レスポンスデータの整形
        $responseData = [
            'id' => $responseData->id,
            'review_id' => $responseData->review_id,
            'author_id' => $responseData->author_id,
            'body' => $responseData->body,
            'created_at' => $responseData->created_at,
            'updated_at' => $responseData->updated_at,
        ];

        return response()->json([
            'message' => 'Review response created',
            'data' => $responseData // 作成されたレスポンスデータ
        ], 201);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Response;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * ReviewController
 *
 * 製品レビューに関するAPIコントローラー
 */

class ReviewController extends Controller
{
    /**
     * Calculate the latest average rating for a product without mutating state.
     */
    private function calculateProductRating(int $productId): float
    {
        $averageRaw = Review::where('product_id', $productId)->avg('rating');
        if ($averageRaw === null) {
            return 0.0;
        }

        return round($averageRaw / 2, 1);
    }

    /**
     * Recalculate and persist the product's average rating.
     */
    private function syncProductRating(int $productId): float
    {
        $average = $this->calculateProductRating($productId);
        if ($product = Product::find($productId)) {
            $product->rating = $average;
            $product->save();
        }

        return $average;
    }

    /**
     * Retrieve the validated rating value, falling back to request input if necessary.
     */
    private function resolveRatingValue(array $validatedData, Request $request): float
    {
        if (array_key_exists('rating', $validatedData)) {
            return (float) $validatedData['rating'];
        }

        $rawValue = $request->input('rating');
        if (!is_numeric($rawValue)) {
            throw ValidationException::withMessages([
                'rating' => '評価の値が不正です。',
            ]);
        }

        $value = (float) $rawValue;
        $scaled = $value * 2;
        if (abs($scaled - round($scaled)) > 0.001 || $value < 0.5 || $value > 5) {
            throw ValidationException::withMessages([
                'rating' => '評価は0.5刻みで0.5から5の範囲で指定してください。',
            ]);
        }

        return $value;
    }

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
        $reviews = Review::with('user')
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->get();

        if ($reviews->isEmpty()) {
            return response()->json([
                'message' => 'List of reviews',
                'data' => [],
                'average_rating' => 0.0,
                'review_count' => 0,
            ]);
        }

        $summary = [
            'average_rating' => $this->calculateProductRating($productId),
            'review_count' => $reviews->count(),
        ];

        $reviews = $reviews->map(fn (Review $review) => $this->transformReview($review));

        return response()->json([
            'message' => 'List of reviews',
            'data' => $reviews,
            ...$summary,
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
            'rating' => [
                'required',
                'numeric',
                'between:0.5,5',
                function (string $attribute, $value, $fail) {
                    $scaled = $value * 2;
                    if (abs($scaled - round($scaled)) > 0.001) {
                        $fail('Rating must be in increments of 0.5.');
                    }
                },
            ],
        ]);

        $authorId = Auth::id();
        if ($authorId === null) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $review = new Review();
        $review->product_id = $productId;
        $review->author_id = $authorId; // 認証済みユーザーのIDを使用
        $review->title = $validatedData['title'];
        $review->body = $validatedData['body'];
        $review->rating = $this->resolveRatingValue($validatedData, $request);
        $review->helpful_count = 0; // 初期値は0
        $review->created_at = now(); // 作成日時
        $review->updated_at = now(); // 更新日時
        $review->save();
        $review->load('user');

        $average = $this->syncProductRating($productId);
        $count = Review::where('product_id', $productId)->count();

        $responseData = $this->transformReview($review);
        return response()->json([
            'message' => 'Review created successfully',
            'data' => $responseData,
            'average_rating' => $average,
            'review_count' => $count,
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
            'rating' => [
                'required',
                'numeric',
                'between:0.5,5',
                function (string $attribute, $value, $fail) {
                    $scaled = $value * 2;
                    if (abs($scaled - round($scaled)) > 0.001) {
                        $fail('Rating must be in increments of 0.5.');
                    }
                },
            ],
        ]);
        $review = Review::findOrFail($reviewId);

        if ($review->author_id !== Auth::id()) {
            return response()->json([
                'message' => 'You are not allowed to update this review.',
            ], 403);
        }
        // レビューの更新処理
        $review->title = $validatedData['title'];
        $review->body = $validatedData['body'];
        $review->rating = $this->resolveRatingValue($validatedData, $request);
        $review->save();

        $review->load('user');

        $productId = $review->product_id;
        $average = 0.0;
        $count = 0;
        if ($productId) {
            $average = $this->syncProductRating((int) $productId);
            $count = Review::where('product_id', $productId)->count();
        }

        // レスポンスデータの整形
        $responseData = $this->transformReview($review);

        return response()->json([
            'message' => 'Review updated successfully',
            'data' => $responseData,
            'average_rating' => $average,
            'review_count' => $count,
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

        if ($review->author_id !== Auth::id()) {
            return response()->json([
                'message' => 'You are not allowed to delete this review.',
            ], 403);
        }

        $productId = $review->product_id;
        $review->delete();

        if ($productId) {
            $average = $this->syncProductRating((int) $productId);
            $count = Review::where('product_id', $productId)->count();

            return response()->json([
                'message' => 'Review deleted successfully',
                'average_rating' => $average,
                'review_count' => $count,
            ]);
        }

        return response()->json([
            'message' => 'Review deleted successfully',
            'average_rating' => 0.0,
            'review_count' => 0,
        ]);
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

        private function transformReview(Review $review): array
        {
            $user = $review->relationLoaded('user')
                ? $review->user
                : $review->user()->first();

            $authorName = null;
            $authorAvatar = null;

            if ($user !== null) {
                $authorName = $user->display_name ?: $user->name;
                $authorAvatar = $this->normalizePublicUrl($user->avatar_url);
            }

            return [
                'id' => $review->id,
                'product_id' => $review->product_id,
                'author_id' => $review->author_id,
                'author_name' => $authorName,
                'author_avatar_url' => $authorAvatar,
                'title' => $review->title,
                'body' => $review->body,
                'rating' => $review->rating,
                'helpful_count' => $review->helpful_count,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ];
        }

        private function normalizePublicUrl(?string $path): ?string
        {
            if ($path === null || $path === '') {
                return null;
            }

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            return url($path);
        }
}
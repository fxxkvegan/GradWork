<?php

namespace App\Support\Presenters;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewNotificationRead;

class ReviewNotificationPresenter
{
    /**
     * @param  iterable<Review>  $reviews
     * @param  iterable<ReviewNotificationRead>  $readStates
     */
    public static function presentMany(iterable $reviews, iterable $readStates): array
    {
        $indexedStates = [];
        foreach ($readStates as $state) {
            $indexedStates[$state->review_id] = $state;
        }

        $items = [];
        foreach ($reviews as $review) {
            $items[] = self::present($review, $indexedStates[$review->id] ?? null);
        }

        return $items;
    }

    public static function present(Review $review, ?ReviewNotificationRead $readState = null): array
    {
        $product = $review->relationLoaded('product') ? $review->product : $review->product()->first();
        $reviewer = $review->relationLoaded('user') ? $review->user : $review->user()->first();

        $productImage = null;
        if ($product instanceof Product) {
            $images = ProductPresenter::imageUrls($product);
            $productImage = $images[0] ?? null;
        }

        $reviewerName = '匿名ユーザー';
        if ($reviewer !== null) {
            $reviewerName = $reviewer->display_name ?: ($reviewer->name ?: $reviewerName);
        }

        return [
            'id' => $review->id,
            'review_id' => $review->id,
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'product_image_url' => $productImage,
            'reviewer_id' => $reviewer?->id,
            'reviewer_name' => $reviewerName,
            'reviewer_avatar_url' => ProductPresenter::normalizePublicUrl($reviewer?->avatar_url ?? null),
            'rating' => $review->rating,
            'title' => $review->title,
            'body' => $review->body,
            'created_at' => optional($review->created_at)->toIso8601String(),
            'is_read' => $readState !== null && $readState->read_at !== null,
            'read_at' => optional($readState?->read_at)->toIso8601String(),
        ];
    }
}

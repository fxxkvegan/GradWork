<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUpvote;
use Illuminate\Http\Request;

class ProductUpvoteController extends Controller
{
    // POST /products/{productId}/upvote
    public function store(Request $request, $productId)
    {
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $productId,
            ], 400);
        }

        $user = $request->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        Product::findOrFail($productId);

        ProductUpvote::firstOrCreate([
            'product_id' => $productId,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Upvoted',
        ], 200);
    }

    // DELETE /products/{productId}/upvote
    public function destroy(Request $request, $productId)
    {
        $productId = intval($productId);
        if ($productId <= 0) {
            return response()->json([
                'message' => 'Invalid product ID',
                'data' => $productId,
            ], 400);
        }

        $user = $request->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        ProductUpvote::where('product_id', $productId)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'Upvote removed',
        ], 200);
    }
}

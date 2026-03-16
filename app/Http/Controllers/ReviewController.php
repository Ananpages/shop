<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    // POST /api/reviews
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string|max:1000',
            'order_id'   => 'nullable|exists:orders,id',
            'images'     => 'nullable|array',
        ]);

        $existing = Review::where('product_id', $request->product_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'You have already reviewed this product'], 409);
        }

        // Check if verified purchase
        $isVerified = false;
        if ($request->order_id) {
            $isVerified = Order::where('id', $request->order_id)
                ->where('buyer_id', $request->user()->id)
                ->where('status', 'delivered')
                ->exists();
        }

        $review = Review::create([
            'id'          => Str::uuid(),
            'product_id'  => $request->product_id,
            'user_id'     => $request->user()->id,
            'order_id'    => $request->order_id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
            'images'      => $request->images ?? [],
            'is_verified' => $isVerified,
        ]);

        // Recalculate product rating
        Product::findOrFail($request->product_id)->recalculateRating();

        return response()->json([
            'success' => true,
            'message' => 'Review submitted!',
            'data'    => $review->load('user:id,name,avatar'),
        ], 201);
    }
}

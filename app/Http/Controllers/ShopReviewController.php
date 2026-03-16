<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopReview;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ShopReviewController extends Controller
{
    // POST /api/shops/{shopId}/reviews
    public function store(Request $request, string $shopId): JsonResponse
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $shop   = Shop::findOrFail($shopId);
        $userId = $request->user()->id;

        // Cannot review your own shop
        if ($shop->user_id === $userId) {
            return Response::json(['success' => false, 'message' => 'You cannot review your own shop'], 403);
        }

        // Check verified purchase (has delivered order from this shop)
        $isVerified = Order::where('buyer_id', $userId)
            ->where('shop_id', $shopId)
            ->where('status', 'delivered')
            ->exists();

        // Update or create
        $review = ShopReview::updateOrCreate(
            ['shop_id' => $shopId, 'user_id' => $userId],
            [
                'id'          => Str::uuid(),
                'rating'      => $request->rating,
                'comment'     => $request->comment,
                'is_verified' => $isVerified,
            ]
        );

        // Recalculate shop average rating
        $avg   = ShopReview::where('shop_id', $shopId)->avg('rating');
        $count = ShopReview::where('shop_id', $shopId)->count();
        $shop->update(['rating' => round($avg, 1), 'total_reviews' => $count]);

        return Response::json([
            'success' => true,
            'message' => 'Review submitted!',
            'data'    => array_merge($review->toArray(), [
                'user_name' => $request->user()->name,
            ]),
        ], 201);
    }

    // GET /api/shops/{shopId}/reviews
    public function index(string $shopId): JsonResponse
    {
        $reviews = ShopReview::where('shop_id', $shopId)
            ->with('user:id,name,avatar')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'rating'      => $r->rating,
                'comment'     => $r->comment,
                'is_verified' => $r->is_verified,
                'user_name'   => $r->user?->name ?? 'Anonymous',
                'user_avatar' => $r->user?->avatar,
                'created_at'  => $r->created_at,
            ]);

        return Response::json(['success' => true, 'data' => $reviews]);
    }
}

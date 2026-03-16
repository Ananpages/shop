<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Models\RecentlyViewed;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    // GET /api/notifications
    public function index(Request $request): JsonResponse
    {
        $notifications = UserNotification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'notifications' => $notifications,
                'unread_count'  => $unreadCount,
            ],
        ]);
    }

    // PUT /api/notifications/read-all
    public function readAll(Request $request): JsonResponse
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'All marked as read']);
    }

    // GET /api/recently-viewed
    public function recentlyViewed(Request $request): JsonResponse
    {
        $items = RecentlyViewed::where('user_id', $request->user()->id)
            ->with([
                'product' => fn($q) => $q->where('status', 'active')
                    ->with('shop:id,name,slug'),
            ])
            ->orderByDesc('viewed_at')
            ->take(20)
            ->get()
            ->filter(fn($r) => $r->product !== null)
            ->map(fn($r) => [
                'id'             => $r->product->id,
                'name'           => $r->product->name,
                'original_price' => $r->product->original_price,
                'discount_price' => $r->product->discount_price,
                'images'         => $r->product->images,
                'rating'         => $r->product->rating,
                'shop_name'      => $r->product->shop->name ?? '',
                'viewed_at'      => $r->viewed_at,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $items]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    // GET /api/wishlist
    public function index(Request $request): JsonResponse
    {
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with([
                'product' => fn($q) => $q->where('status', 'active')
                    ->with('shop:id,name,slug'),
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn($w) => $w->product !== null)
            ->map(fn($w) => [
                'id'             => $w->id,
                'product_id'     => $w->product_id,
                'name'           => $w->product->name,
                'original_price' => $w->product->original_price,
                'discount_price' => $w->product->discount_price,
                'images'         => $w->product->images,
                'rating'         => $w->product->rating,
                'stock'          => $w->product->stock,
                'shop_name'      => $w->product->shop->name ?? '',
                'shop_slug'      => $w->product->shop->slug ?? '',
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $items]);
    }

    // POST /api/wishlist/toggle
    public function toggle(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $existing = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'data'    => ['wishlisted' => false],
                'message' => 'Removed from wishlist',
            ]);
        }

        Wishlist::create([
            'id'         => Str::uuid(),
            'user_id'    => $request->user()->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['wishlisted' => true],
            'message' => 'Added to wishlist',
        ]);
    }
}

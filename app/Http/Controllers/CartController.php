<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CartController extends Controller
{
    // GET /api/cart
    public function index(Request $request): JsonResponse
    {
        $items = CartItem::where('user_id', $request->user()->id)
            ->with([
                'product' => fn($q) => $q->where('status', 'active')
                    ->with('shop:id,name,slug'),
            ])
            ->get()
            ->filter(fn($item) => $item->product !== null)
            ->values();

        $total = $items->sum(fn($item) =>
            ($item->product->discount_price ?? $item->product->original_price) * $item->quantity
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'items' => $items->map(fn($item) => [
                    'id'             => $item->id,
                    'product_id'     => $item->product_id,
                    'quantity'       => $item->quantity,
                    'name'           => $item->product->name,
                    'original_price' => $item->product->original_price,
                    'discount_price' => $item->product->discount_price,
                    'stock'          => $item->product->stock,
                    'images'         => $item->product->images,
                    'district'       => $item->product->district,
                    'shop_id'        => $item->product->shop_id,
                    'shop_name'      => $item->product->shop->name ?? '',
                    'shop_slug'      => $item->product->shop->slug ?? '',
                ]),
                'total' => $total,
                'count' => $items->count(),
            ],
        ]);
    }

    // POST /api/cart
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'sometimes|integer|min:1',
        ]);

        $product  = Product::active()->findOrFail($request->product_id);
        $quantity = $request->get('quantity', 1);

        if ($product->stock < $quantity) {
            return response()->json(['success' => false, 'message' => 'Insufficient stock'], 400);
        }

        $existing = CartItem::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $quantity;
            if ($product->stock < $newQty) {
                return response()->json(['success' => false, 'message' => 'Insufficient stock'], 400);
            }
            $existing->update(['quantity' => $newQty]);
        } else {
            CartItem::create([
                'id'         => Str::uuid(),
                'user_id'    => $request->user()->id,
                'product_id' => $product->id,
                'quantity'   => $quantity,
            ]);
        }

        $count = CartItem::where('user_id', $request->user()->id)->count();

        return response()->json([
            'success' => true,
            'message' => 'Added to cart',
            'data'    => ['cart_count' => $count],
        ]);
    }

    // PUT /api/cart/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        $item = CartItem::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('product')
            ->firstOrFail();

        if ($item->product->stock < $request->quantity) {
            return response()->json(['success' => false, 'message' => 'Insufficient stock'], 400);
        }

        $item->update(['quantity' => $request->quantity]);

        return response()->json(['success' => true, 'message' => 'Cart updated']);
    }

    // DELETE /api/cart/{id}
    public function destroy(Request $request, string $id): JsonResponse
    {
        CartItem::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Removed from cart']);
    }

    // DELETE /api/cart
    public function clear(Request $request): JsonResponse
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return response()->json(['success' => true, 'message' => 'Cart cleared']);
    }
}

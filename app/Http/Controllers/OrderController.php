<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\Shop;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    // ==================== HELPERS ====================

    private function notify(string $userId, string $title, string $message, string $type = 'info', ?string $refId = null): void
    {
        UserNotification::create([
            'id'      => Str::uuid(),
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
            'data'    => $refId ? ['ref_id' => $refId] : null,
            'is_read' => false,
        ]);
    }

    // ==================== ROUTES ====================

    // GET /api/orders
    public function index(Request $request): JsonResponse
    {
        $query = Order::forBuyer($request->user()->id)
            ->with('shop:id,name,logo,slug');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return Response::json(['success' => true, 'data' => $orders]);
    }

    // GET /api/orders/{id}
    public function show(Request $request, string $id): JsonResponse
    {
        $order = Order::with([
            'shop:id,name,logo,phone',
            'buyer:id,name',
            'statusHistory.actor:id,name',
        ])->findOrFail($id);

        $user = $request->user();
        $shop = Shop::where('user_id', $user->id)->first();

        if ($order->buyer_id !== $user->id && !$user->isAdmin()) {
            if (!$shop || $order->shop_id !== $shop->id) {
                return Response::json(['success' => false, 'message' => 'Forbidden'], 403);
            }
        }

        return Response::json([
            'success' => true,
            'data'    => ['order' => $order, 'history' => $order->statusHistory],
        ]);
    }

    // POST /api/orders
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'delivery_district'  => 'required|string',
            'delivery_address'   => 'required|string',
            'buyer_phone'        => 'required|string',
            'notes'              => 'nullable|string',
            'payment_method'     => 'sometimes|in:cash,mobile_money,card',
        ]);

        return DB::transaction(function () use ($request) {
            // Group items by shop
            $shopGroups = [];
            foreach ($request->items as $item) {
                $product = Product::active()->lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    return Response::json([
                        'success' => false,
                        'message' => "Insufficient stock for: {$product->name}",
                    ], 400);
                }

                $shopGroups[$product->shop_id][] = array_merge($item, ['product' => $product]);
            }

            $createdOrders = [];

            foreach ($shopGroups as $shopId => $shopItems) {
                $subtotal    = collect($shopItems)->sum(fn($i) => ($i['product']->discount_price ?? $i['product']->original_price) * $i['quantity']);
                $deliveryFee = config('beibe.delivery_fee', 3000);
                $total       = $subtotal + $deliveryFee;

                $orderItems = collect($shopItems)->map(fn($i) => [
                    'product_id'     => $i['product_id'],
                    'name'           => $i['product']->name,
                    'image'          => $i['product']->images[0] ?? '',
                    'quantity'       => $i['quantity'],
                    'price'          => $i['product']->discount_price ?? $i['product']->original_price,
                    'original_price' => $i['product']->original_price,
                ])->toArray();

                $order = Order::create([
                    'id'                => Str::uuid(),
                    'order_number'      => Order::generateOrderNumber(),
                    'buyer_id'          => $request->user()->id,
                    'shop_id'           => $shopId,
                    'items'             => $orderItems,
                    'subtotal'          => $subtotal,
                    'delivery_fee'      => $deliveryFee,
                    'total'             => $total,
                    'delivery_district' => $request->delivery_district,
                    'delivery_address'  => $request->delivery_address,
                    'buyer_phone'       => $request->buyer_phone,
                    'notes'             => $request->notes,
                    'payment_method'    => $request->get('payment_method', 'cash'),
                ]);

                // Reduce stock
                foreach ($shopItems as $item) {
                    $item['product']->decrement('stock', $item['quantity']);
                    $item['product']->increment('total_sales', $item['quantity']);
                }

                // Status history
                OrderStatusHistory::create([
                    'id'         => Str::uuid(),
                    'order_id'   => $order->id,
                    'status'     => 'pending',
                    'note'       => 'Order placed',
                    'created_by' => $request->user()->id,
                ]);

                // ✅ FIXED: was UserNotification::send() which doesn't exist
                $shop = Shop::find($shopId);
                $this->notify(
                    $shop->user_id,
                    '🛍️ New Order!',
                    "New order #{$order->order_number} received",
                    'order',
                    $order->id
                );

                $createdOrders[] = [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'total'        => $total,
                ];
            }

            // Clear cart
            foreach ($request->items as $item) {
                CartItem::where('user_id', $request->user()->id)
                    ->where('product_id', $item['product_id'])
                    ->delete();
            }

            return Response::json([
                'success' => true,
                'message' => 'Order placed successfully!',
                'data'    => $createdOrders,
            ], 201);
        });
    }

    // GET /api/orders/seller/list
    public function sellerOrders(Request $request): JsonResponse
    {
        $shop = Shop::where('user_id', $request->user()->id)->firstOrFail();

        $query = Order::forShop($shop->id)->with('buyer:id,name,phone');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return Response::json(['success' => true, 'data' => $orders]);
    }

    // PUT /api/orders/{id}/status
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:accepted,preparing,out_for_delivery,delivered,cancelled',
            'note'   => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);

        if (!$order->canBeUpdatedBy($request->user())) {
            return Response::json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $order->update(['status' => $request->status]);

        OrderStatusHistory::create([
            'id'         => Str::uuid(),
            'order_id'   => $order->id,
            'status'     => $request->status,
            'note'       => $request->note,
            'created_by' => $request->user()->id,
        ]);

        $labels = [
            'accepted'         => 'accepted',
            'preparing'        => 'being prepared',
            'out_for_delivery' => 'out for delivery',
            'delivered'        => 'delivered',
            'cancelled'        => 'cancelled',
        ];

        // ✅ FIXED: was UserNotification::send() which doesn't exist
        $this->notify(
            $order->buyer_id,
            '📦 Order Update',
            "Your order #{$order->order_number} is {$labels[$request->status]}",
            'order',
            $order->id
        );

        return Response::json(['success' => true, 'message' => 'Order status updated']);
    }
}

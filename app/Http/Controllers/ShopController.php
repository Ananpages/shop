<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    // GET /api/shops
    public function index(Request $request): JsonResponse
    {
        $query = Shop::approved()
            ->with('owner:id,name,phone')
            ->withCount(['products' => fn($q) => $q->where('status', '!=', 'deleted')]);

        if ($request->search) {
            $query->search($request->search);
        }
        if ($request->district) {
            $query->where('district', $request->district);
        }

        $shops = $query->orderBy('total_sales', 'desc')
            ->paginate($request->get('limit', 20));

        // ✅ Map products_count → product_count for frontend
        $items = collect($shops->items())->map(function ($shop) {
            $arr = $shop->toArray();
            $arr['product_count'] = $shop->products_count ?? 0;
            return $arr;
        });

        return Response::json([
            'success' => true,
            'data'    => [
                'shops' => $items,
                'total' => $shops->total(),
                'page'  => $shops->currentPage(),
                'pages' => $shops->lastPage(),
            ],
        ]);
    }

    // GET /api/shops/{slug}
    public function show(string $slug): JsonResponse
    {
        // ✅ Check both slug AND custom_slug so shopbeibe.com/shop/anan works
        $shop = Shop::approved()
            ->with('owner:id,name,phone')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                  ->orWhere('custom_slug', $slug);
            })
            ->firstOrFail();

        $products = Product::active()
            ->where('shop_id', $shop->id)
            ->with('category:id,name,slug')
            ->orderBy('created_at', 'desc')
            ->get();

        return Response::json([
            'success' => true,
            'data'    => ['shop' => $shop, 'products' => $products],
        ]);
    }

    // POST /api/shops
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:50|unique:shops,name',
            'phone'       => 'required|string|max:15',
            'district'    => 'required|string',
            'description' => 'nullable|string|max:500',
            'logo'        => 'nullable|string',
        ]);

        if ($request->user()->shop()->exists()) {
            return Response::json(['success' => false, 'message' => 'You already have a shop'], 409);
        }

        $slug = Str::slug($request->name);
        if (Shop::where('slug', $slug)->exists()) {
            return Response::json(['success' => false, 'message' => 'Shop name already taken'], 409);
        }

        // ✅ Auto-generate custom_slug from shop name, append random suffix if taken
        $customSlug = $slug;
        if (Shop::where('custom_slug', $customSlug)->exists()) {
            $customSlug = $slug . '-' . Str::random(4);
        }

        $shop = Shop::create([
            'id'          => Str::uuid(),
            'user_id'     => $request->user()->id,
            'name'        => trim($request->name),
            'slug'        => $slug,
            'custom_slug' => $customSlug, // ✅ set immediately on creation
            'description' => $request->description,
            'phone'       => $request->phone,
            'district'    => $request->district,
            'logo'        => $request->logo,
            'status'      => 'pending',
        ]);

        // Upgrade user role to seller
        $request->user()->update(['role' => 'seller']);

        return Response::json([
            'success' => true,
            'message' => 'Shop created! Awaiting admin approval.',
            'data'    => $shop,
        ], 201);
    }

    // PUT /api/shops/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        $shop = Shop::findOrFail($id);

        if ($shop->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return Response::json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'name'        => 'sometimes|string|max:50',
            'description' => 'sometimes|nullable|string',
            'phone'       => 'sometimes|string',
            'district'    => 'sometimes|string',
            'logo'        => 'sometimes|nullable|string',
            'banner'      => 'sometimes|nullable|string',
        ]);

        $shop->update($request->only('name', 'description', 'phone', 'district', 'logo', 'banner'));

        return Response::json(['success' => true, 'data' => $shop->fresh()]);
    }

    // GET /api/shops/my/dashboard
    public function dashboard(Request $request): JsonResponse
    {
        $shop = Shop::where('user_id', $request->user()->id)->firstOrFail();

        $stats = [
            'total_products' => Product::where('shop_id', $shop->id)->where('status', 'active')->count(),
            'total_orders'   => Order::where('shop_id', $shop->id)->count(),
            'pending_orders' => Order::where('shop_id', $shop->id)->where('status', 'pending')->count(),
            'total_revenue'  => Order::where('shop_id', $shop->id)->where('status', 'delivered')->sum('total'),
            'today_orders'   => Order::where('shop_id', $shop->id)->whereDate('created_at', today())->count(),
        ];

        $recentOrders = Order::where('shop_id', $shop->id)
            ->with('buyer:id,name,phone')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $lowStockProducts = Product::where('shop_id', $shop->id)
            ->where('status', 'active')
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->get();

        return Response::json([
            'success' => true,
            'data'    => compact('shop', 'stats', 'recentOrders', 'lowStockProducts'),
        ]);
    }
}

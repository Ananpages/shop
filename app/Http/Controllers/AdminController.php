<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Order;
use App\Models\Category;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class AdminController extends Controller
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

    // ==================== STATS ====================

    public function stats(): JsonResponse
    {
        $stats = [
            'total_users'    => User::count(),
            'total_sellers'  => User::whereIn('role', ['seller', 'admin'])->count(),
            'total_shops'    => Shop::count(),
            'approved_shops' => Shop::where('status', 'approved')->count(),
            'pending_shops'  => Shop::where('status', 'pending')->count(),
            'total_products' => Product::where('status', 'active')->count(),
            'total_orders'   => Order::count(),
            'total_revenue'  => Order::where('status', 'delivered')->sum('total'),
            'today_orders'   => Order::whereDate('created_at', today())->count(),
            'today_revenue'  => Order::whereDate('created_at', today())->where('status', 'delivered')->sum('total'),
        ];

        $recentOrders = Order::with([
            'buyer:id,name',
            'shop:id,name',
        ])->orderBy('created_at', 'desc')->take(15)->get();

        return Response::json([
            'success' => true,
            'data'    => compact('stats', 'recentOrders'),
        ]);
    }

    // ==================== USERS ====================

    public function users(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->role) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(30);

        return Response::json([
            'success' => true,
            'data'    => ['users' => $users->items(), 'total' => $users->total()],
        ]);
    }

    public function toggleUser(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return Response::json(['success' => false, 'message' => 'Cannot modify admin accounts'], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return Response::json([
            'success' => true,
            'message' => $user->is_active ? 'User activated' : 'User suspended',
        ]);
    }

    // ==================== SHOPS ====================

    public function shops(Request $request): JsonResponse
    {
        $query = Shop::with('owner:id,name,phone,email')
            ->withCount(['products' => fn($q) => $q->where('status', 'active')]);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $shops = $query->orderBy('created_at', 'desc')->paginate(30);

        return Response::json([
            'success' => true,
            'data'    => ['shops' => $shops->items(), 'total' => $shops->total()],
        ]);
    }

    public function approveShop(string $id): JsonResponse
    {
        $shop = Shop::findOrFail($id);
        $shop->update(['status' => 'approved']);

        // ✅ FIXED: was UserNotification::send() which doesn't exist
        $this->notify(
            $shop->user_id,
            '🎉 Shop Approved!',
            "Your shop \"{$shop->name}\" has been approved. Start selling now!",
            'shop',
            $shop->id
        );

        // Also update the owner's role to seller
        User::where('id', $shop->user_id)->update(['role' => 'seller']);

        return Response::json(['success' => true, 'message' => 'Shop approved']);
    }

    public function rejectShop(Request $request, string $id): JsonResponse
    {
        $shop = Shop::findOrFail($id);
        $shop->update(['status' => 'rejected']);

        $reason = $request->reason ?? 'Does not meet our marketplace standards';

        // ✅ FIXED: was UserNotification::send() which doesn't exist
        $this->notify(
            $shop->user_id,
            '❌ Shop Not Approved',
            "Your shop \"{$shop->name}\" was not approved. Reason: {$reason}",
            'shop',
            $shop->id
        );

        return Response::json(['success' => true, 'message' => 'Shop rejected']);
    }

    public function suspendShop(string $id): JsonResponse
    {
        $shop = Shop::findOrFail($id);
        $shop->update(['status' => 'suspended']);

        // ✅ FIXED: was UserNotification::send() which doesn't exist
        $this->notify(
            $shop->user_id,
            '⛔ Shop Suspended',
            "Your shop \"{$shop->name}\" has been suspended. Contact support for assistance.",
            'shop',
            $shop->id
        );

        return Response::json(['success' => true, 'message' => 'Shop suspended']);
    }

    // ==================== PRODUCTS ====================

    public function products(Request $request): JsonResponse
    {
        $query = Product::with([
            'shop:id,name',
            'category:id,name',
            'seller:id,name',
        ])->where('status', '!=', 'deleted');

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        if ($request->shop_id) {
            $query->where('shop_id', $request->shop_id);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(30);

        return Response::json([
            'success' => true,
            'data'    => ['products' => $products->items(), 'total' => $products->total()],
        ]);
    }

    public function removeProduct(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update(['status' => 'deleted']);

        return Response::json(['success' => true, 'message' => 'Product removed']);
    }

    // ==================== ORDERS ====================

    public function orders(Request $request): JsonResponse
    {
        $query = Order::with([
            'buyer:id,name,phone',
            'shop:id,name',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->shop_id) {
            $query->where('shop_id', $request->shop_id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(30);

        return Response::json([
            'success' => true,
            'data'    => ['orders' => $orders->items(), 'total' => $orders->total()],
        ]);
    }

    // ==================== CATEGORIES ====================

    public function createCategory(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:50|unique:categories,name',
            'icon'       => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $category = Category::create([
            'id'         => Str::uuid(),
            'name'       => $request->name,
            'slug'       => Str::slug($request->name),
            'icon'       => $request->icon,
            'sort_order' => $request->get('sort_order', 99),
        ]);

        return Response::json(['success' => true, 'data' => $category], 201);
    }
}

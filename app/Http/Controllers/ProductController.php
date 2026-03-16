<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use App\Models\Category;
use App\Models\RecentlyViewed;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // GET /api/products
    public function index(Request $request): JsonResponse
    {
        $query = Product::active()
            ->with([
                'shop:id,name,slug,custom_slug,logo,rating,district,is_verified,verification_expires_at',
                'category:id,name,slug',
                'seller:id,name',
            ]);

        if ($request->search)   $query->search($request->search);
        if ($request->category && $request->category !== 'all') $query->inCategory($request->category);
        if ($request->district) $query->where('district', $request->district);
        if ($request->min_price) $query->whereRaw('COALESCE(discount_price, original_price) >= ?', [$request->min_price]);
        if ($request->max_price) $query->whereRaw('COALESCE(discount_price, original_price) <= ?', [$request->max_price]);
        if ($request->shop_id)  $query->where('shop_id', $request->shop_id);

        $query->sorted($request->get('sort', 'newest'));
        $products = $query->paginate($request->get('limit', 20));

        $userId = $request->user()?->id;
        $items  = collect($products->items())->map(function ($p) use ($userId) {
            $arr = $p->toArray();
            $arr['discount_percent']           = $p->discount_percent;
            $arr['is_wishlisted']              = $userId
                ? Wishlist::where('user_id', $userId)->where('product_id', $p->id)->exists()
                : false;
            // ✅ Shop verification fields so badge shows on product cards
            $arr['shop_is_verified']              = $p->shop?->is_verified ?? false;
            $arr['shop_verification_expires_at']  = $p->shop?->verification_expires_at;
            $arr['shop_name']                     = $p->shop?->name;
            $arr['shop_slug']                     = $p->shop?->slug;
            $arr['shop_custom_slug']              = $p->shop?->custom_slug;
            return $arr;
        });

        return Response::json([
            'success' => true,
            'data'    => [
                'products' => $items,
                'total'    => $products->total(),
                'page'     => $products->currentPage(),
                'pages'    => $products->lastPage(),
            ],
        ]);
    }

    // GET /api/products/featured
    public function featured(): JsonResponse
    {
        $products = Product::active()
            ->with('shop:id,name,slug', 'category:id,name')
            ->whereHas('shop', fn($q) => $q->where('status', 'approved'))
            ->orderByDesc('total_sales')
            ->orderByDesc('rating')
            ->take(20)
            ->get();

        return Response::json(['success' => true, 'data' => $products]);
    }

    // GET /api/products/{id}
    public function show(Request $request, string $id): JsonResponse
    {
        $product = Product::active()
            ->with([
                'shop:id,name,slug,logo,rating,total_reviews,phone,district',
                'category:id,name,slug',
                'seller:id,name',
            ])
            ->findOrFail($id);

        $product->increment('total_views');

        $reviews = $product->reviews()
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $ratingDistribution = collect([5, 4, 3, 2, 1])->map(fn($star) => [
            'star'  => $star,
            'count' => $product->reviews()->where('rating', $star)->count(),
        ]);

        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('shop:id,name,slug')
            ->take(8)
            ->get();

        if ($request->user()) {
            RecentlyViewed::updateOrCreate(
                ['user_id' => $request->user()->id, 'product_id' => $product->id],
                ['id' => Str::uuid(), 'viewed_at' => now()]
            );
            $product->is_wishlisted = Wishlist::where('user_id', $request->user()->id)
                ->where('product_id', $product->id)->exists();
        }

        // ✅ Flatten shop fields so frontend can use product.shop_name etc.
        $data = $product->toArray();
        $data['discount_percent'] = $product->discount_percent;
        $data['shop_id_real']     = $product->shop_id;
        $data['shop_name']        = $product->shop?->name;
        $data['shop_slug']        = $product->shop?->slug;
        $data['shop_logo']        = $product->shop?->logo;
        $data['shop_phone']       = $product->shop?->phone;
        $data['shop_district']    = $product->shop?->district;
        $data['shop_rating']      = $product->shop?->rating;
        $data['shop_reviews']     = $product->shop?->total_reviews;
        $data['shop_custom_slug'] = $product->shop?->custom_slug;
        $data['category_name']    = $product->category?->name;

        return Response::json([
            'success' => true,
            'data'    => compact('data', 'reviews', 'ratingDistribution', 'related'),
        ]);
    }

    // POST /api/products
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'           => 'required|string|max:200',
            'category_id'    => 'required|exists:categories,id',
            'original_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:original_price',
            'stock'          => 'required|integer|min:0',
            'district'       => 'required|string',
            'description'    => 'nullable|string',
            'images'         => 'nullable|array',
            'specifications' => 'nullable|array',
            'tags'           => 'nullable|array',
        ]);

        $shop = Shop::where('user_id', $request->user()->id)
            ->where('status', 'approved')
            ->first();

        if (!$shop) {
            return Response::json(['success' => false, 'message' => 'Your shop is not approved yet'], 403);
        }

        $product = Product::create([
            'id'             => Str::uuid(),
            'shop_id'        => $shop->id,
            'seller_id'      => $request->user()->id,
            'category_id'    => $request->category_id,
            'name'           => trim($request->name),
            'slug'           => Str::slug($request->name) . '-' . Str::random(6),
            'description'    => $request->description,
            'original_price' => $request->original_price,
            'discount_price' => $request->discount_price,
            'stock'          => $request->stock,
            'district'       => $request->district,
            'images'         => $request->images ?? [],
            'specifications' => $request->specifications ?? [],
            'tags'           => $request->tags ?? [],
        ]);

        return Response::json(['success' => true, 'data' => $product->load('category', 'shop')], 201);
    }

    // PUT /api/products/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        if ($product->seller_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return Response::json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'original_price' => 'sometimes|numeric|min:0',
            'discount_price' => 'sometimes|nullable|numeric|min:0',
            'stock'          => 'sometimes|integer|min:0',
            'images'         => 'sometimes|array',
            'specifications' => 'sometimes|array',
            'tags'           => 'sometimes|array',
        ]);

        $product->update($request->only(
            'name', 'description', 'category_id', 'original_price',
            'discount_price', 'stock', 'district', 'images',
            'specifications', 'tags', 'status'
        ));

        return Response::json(['success' => true, 'data' => $product->fresh()]);
    }

    // DELETE /api/products/{id}
    public function destroy(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        if ($product->seller_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return Response::json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $product->update(['status' => 'deleted']);

        return Response::json(['success' => true, 'message' => 'Product deleted']);
    }

    // GET /api/products/my/list
    public function myProducts(Request $request): JsonResponse
    {
        $shop = Shop::where('user_id', $request->user()->id)->first();

        if (!$shop) {
            return Response::json(['success' => true, 'data' => []]);
        }

        $products = Product::where('shop_id', $shop->id)
            ->where('status', '!=', 'deleted')
            ->with('category:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return Response::json(['success' => true, 'data' => $products]);
    }
}

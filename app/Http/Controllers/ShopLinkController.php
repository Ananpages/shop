<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ShopLinkController extends Controller
{
    // GET /api/shops/my/link
    public function get(Request $request): JsonResponse
    {
        $shop = Shop::where('user_id', $request->user()->id)->first();
        if (!$shop) {
            return Response::json(['success' => false, 'message' => 'No shop found'], 404);
        }

        return Response::json([
            'success' => true,
            'data'    => [
                'custom_slug' => $shop->custom_slug,
                'shop_slug'   => $shop->slug,
                'shop_name'   => $shop->name,
                'link'        => $shop->custom_slug
                    ? "https://shopbeibe.com/shop/{$shop->custom_slug}"
                    : "https://shopbeibe.com/shop/{$shop->slug}",
            ],
        ]);
    }

    // PUT /api/shops/my/link
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'custom_slug' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9\-]+$/', // lowercase letters, numbers, hyphens only
            ],
        ]);

        $shop = Shop::where('user_id', $request->user()->id)->first();
        if (!$shop) {
            return Response::json(['success' => false, 'message' => 'No shop found'], 404);
        }

        $slug = strtolower(trim($request->custom_slug));

        // Check if taken by another shop
        $taken = Shop::where('custom_slug', $slug)
            ->where('id', '!=', $shop->id)
            ->exists();

        // Also check against system slugs
        $reserved = ['admin', 'api', 'www', 'mail', 'shop', 'beibe', 'help', 'support', 'about'];
        if (in_array($slug, $reserved)) {
            return Response::json(['success' => false, 'message' => 'This link name is reserved. Please choose another.'], 422);
        }

        if ($taken) {
            return Response::json(['success' => false, 'message' => 'This link is already taken. Try another name.'], 422);
        }

        $shop->update(['custom_slug' => $slug]);

        return Response::json([
            'success' => true,
            'message' => 'Shop link updated!',
            'data'    => [
                'custom_slug' => $slug,
                'link'        => "https://shopbeibe.com/shop/{$slug}",
            ],
        ]);
    }

    // GET /api/shops/check-slug/{slug} — check availability
    public function checkSlug(string $slug): JsonResponse
    {
        $slug      = strtolower(trim($slug));
        $reserved  = ['admin', 'api', 'www', 'mail', 'shop', 'beibe', 'help', 'support', 'about'];
        $isReserved = in_array($slug, $reserved);
        $isTaken    = Shop::where('custom_slug', $slug)->exists();

        return Response::json([
            'success'   => true,
            'available' => !$isReserved && !$isTaken,
            'reason'    => $isReserved ? 'reserved' : ($isTaken ? 'taken' : null),
        ]);
    }
}

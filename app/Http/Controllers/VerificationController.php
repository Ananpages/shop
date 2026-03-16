<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    // POST /api/shops/verify/request
    public function request(Request $request): JsonResponse
    {
        $shop = Shop::where('user_id', $request->user()->id)->first();

        if (!$shop) {
            return Response::json(['success' => false, 'message' => 'You do not have a shop yet.'], 404);
        }

        // ✅ Check if currently verified and not yet expired
        if ($shop->is_verified && $shop->verification_expires_at && now()->lt($shop->verification_expires_at)) {
            return Response::json([
                'success' => false,
                'message' => 'Your shop is already verified until ' . $shop->verification_expires_at->format('d M Y') . '.',
            ], 400);
        }

        if ($shop->verification_status === 'pending') {
            return Response::json([
                'success' => false,
                'message' => 'Verification request already pending. We will review it shortly.',
            ], 400);
        }

        // ✅ If expired, reset and allow re-request
        $shop->update([
            'verification_status' => 'pending',
            'is_verified'         => false,
        ]);

        UserNotification::create([
            'id'      => Str::uuid(),
            'user_id' => $request->user()->id,
            'title'   => '🔍 Verification Request Received',
            'message' => "Your monthly verification request for \"{$shop->name}\" is under review.",
            'type'    => 'shop',
            'data'    => ['ref_id' => $shop->id],
            'is_read' => false,
        ]);

        return Response::json([
            'success' => true,
            'message' => 'Verification request submitted! We will review it within 24–48 hours.',
        ]);
    }

    // PUT /api/admin/shops/{id}/verify
    public function approve(string $id): JsonResponse
    {
        $shop = Shop::findOrFail($id);

        // ✅ Set verified for 30 days from now
        $expiresAt = now()->addDays(30);

        $shop->update([
            'is_verified'              => true,
            'verification_status'      => 'approved',
            'verified_at'              => now(),
            'verification_expires_at'  => $expiresAt,
        ]);

        UserNotification::create([
            'id'      => Str::uuid(),
            'user_id' => $shop->user_id,
            'title'   => '✅ Shop Verified!',
            'message' => "Your shop \"{$shop->name}\" is now verified until {$expiresAt->format('d M Y')}. Remember to renew monthly!",
            'type'    => 'shop',
            'data'    => ['ref_id' => $shop->id],
            'is_read' => false,
        ]);

        return Response::json(['success' => true, 'message' => 'Shop verified for 30 days']);
    }

    // PUT /api/admin/shops/{id}/reject-verification
    public function reject(Request $request, string $id): JsonResponse
    {
        $shop   = Shop::findOrFail($id);
        $reason = $request->reason ?? 'Does not meet verification requirements';

        $shop->update([
            'verification_status' => 'rejected',
            'verification_note'   => $reason,
            'is_verified'         => false,
        ]);

        UserNotification::create([
            'id'      => Str::uuid(),
            'user_id' => $shop->user_id,
            'title'   => '❌ Verification Rejected',
            'message' => "Your verification for \"{$shop->name}\" was rejected. Reason: {$reason}",
            'type'    => 'shop',
            'data'    => ['ref_id' => $shop->id],
            'is_read' => false,
        ]);

        return Response::json(['success' => true, 'message' => 'Verification rejected']);
    }
}

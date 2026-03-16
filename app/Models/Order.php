<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\OrderStatusHistory;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'order_number', 'buyer_id', 'shop_id',
        'items', 'subtotal', 'delivery_fee', 'total',
        'delivery_district', 'delivery_address', 'buyer_phone',
        'notes', 'status', 'payment_status', 'payment_method',
    ];

    protected $casts = [
        'items'        => 'array',
        'subtotal'     => 'float',
        'delivery_fee' => 'float',
        'total'        => 'float',
    ];

    // ==================== RELATIONSHIPS ====================

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'asc');
    }

    // ==================== HELPERS ====================

    public static function generateOrderNumber(): string
    {
        return 'BEI' . now()->format('ymdHi') . rand(100, 999);
    }

    public function canBeUpdatedBy(User $user): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isSeller() && $user->shop && $this->shop_id === $user->shop->id) return true;
        return false;
    }

    // ==================== SCOPES ====================

    public function scopeForBuyer($query, string $userId)
    {
        return $query->where('buyer_id', $userId);
    }

    public function scopeForShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}

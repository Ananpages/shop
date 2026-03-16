<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens; // ✅ ADDED

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, HasApiTokens; // ✅ ADDED HasApiTokens

    protected $fillable = [
        'id', 'name', 'phone', 'email', 'password',
        'role', 'avatar', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        // ✅ REMOVED 'password' => 'hashed' — this auto-hashes on set,
        //    which double-hashes since AuthController already calls Hash::make()
        'is_active'         => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function shop()
    {
        return $this->hasOne(Shop::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function recentlyViewed()
    {
        return $this->hasMany(RecentlyViewed::class);
    }

    // ==================== HELPERS ====================

    public function isSeller(): bool
    {
        return in_array($this->role, ['seller', 'admin']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getApprovedShop(): ?Shop
    {
        return $this->shop()->where('status', 'approved')->first();
    }
}

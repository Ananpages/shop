<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Shop extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'user_id', 'name', 'slug', 'description',
        'logo', 'banner', 'phone', 'district', 'status',
        'rating', 'total_reviews', 'total_sales', 'is_verified',
    'verification_status',
    'verification_note',
    'verified_at',
    ];

    protected $casts = [
        'rating'        => 'float',
        'total_reviews' => 'integer',
        'total_sales'   => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // ==================== HELPERS ====================

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function activeProductCount(): int
    {
        return $this->products()->where('status', 'active')->count();
    }

    // Auto-generate slug from name
    public static function generateSlug(string $name): string
    {
        return Str::slug($name);
    }

    // ==================== SCOPES ====================

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }
}

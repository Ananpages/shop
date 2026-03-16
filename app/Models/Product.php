<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'shop_id', 'seller_id', 'category_id',
        'name', 'slug', 'description',
        'original_price', 'discount_price', 'stock', 'district',
        'images', 'specifications', 'tags',
        'rating', 'total_reviews', 'total_views', 'total_sales', 'status',
    ];

    protected $casts = [
        'images'         => 'array',
        'specifications' => 'array',
        'tags'           => 'array',
        'original_price' => 'float',
        'discount_price' => 'float',
        'rating'         => 'float',
        'stock'          => 'integer',
        'total_reviews'  => 'integer',
        'total_views'    => 'integer',
        'total_sales'    => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    // ==================== ACCESSORS ====================

    public function getEffectivePriceAttribute(): float
    {
        return $this->discount_price ?? $this->original_price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (!$this->discount_price || $this->discount_price >= $this->original_price) {
            return 0;
        }
        return (int) round(
            (($this->original_price - $this->discount_price) / $this->original_price) * 100
        );
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock <= 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock > 0 && $this->stock <= 5;
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeInCategory($query, string $categorySlugOrId)
    {
        return $query->whereHas('category', function ($q) use ($categorySlugOrId) {
            $q->where('slug', $categorySlugOrId)->orWhere('id', $categorySlugOrId);
        });
    }

    public function scopeSorted($query, string $sort = 'newest')
    {
        return match ($sort) {
            'price_asc'    => $query->orderByRaw('COALESCE(discount_price, original_price) ASC'),
            'price_desc'   => $query->orderByRaw('COALESCE(discount_price, original_price) DESC'),
            'popular'      => $query->orderBy('total_views', 'desc'),
            'top_rated'    => $query->orderBy('rating', 'desc'),
            'best_selling' => $query->orderBy('total_sales', 'desc'),
            default        => $query->orderBy('created_at', 'desc'),
        };
    }

    // ==================== HELPERS ====================

    public function recalculateRating(): void
    {
        $avg   = $this->reviews()->avg('rating') ?? 0;
        $count = $this->reviews()->count();
        $this->update(['rating' => round($avg, 1), 'total_reviews' => $count]);
    }
}

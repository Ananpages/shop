<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CartItem extends Model
{
    use HasUuids;

    protected $fillable = ['id', 'user_id', 'product_id', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getSubtotalAttribute(): float
    {
        return ($this->product->discount_price ?? $this->product->original_price) * $this->quantity;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

// ==================== REVIEW ====================
class Review extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'product_id', 'user_id', 'order_id',
        'rating', 'comment', 'images', 'is_verified',
    ];

    protected $casts = [
        'images'      => 'array',
        'rating'      => 'integer',
        'is_verified' => 'boolean',
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function order()   { return $this->belongsTo(Order::class); }
}

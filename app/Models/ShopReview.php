<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopReview extends Model
{
    protected $table     = 'shop_reviews';
    protected $fillable  = ['id', 'shop_id', 'user_id', 'rating', 'comment', 'is_verified'];
    protected $keyType   = 'string';
    public $incrementing = false;

    protected $casts = [
        'rating'      => 'integer',
        'is_verified' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function shop() { return $this->belongsTo(Shop::class); }
}

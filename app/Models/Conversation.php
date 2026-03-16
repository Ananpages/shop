<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'id', 'buyer_id', 'seller_id', 'shop_id',
        'last_message', 'last_message_at',
        'buyer_unread', 'seller_unread',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'last_message_at' => 'datetime',
        'buyer_unread'    => 'integer',
        'seller_unread'   => 'integer',
    ];

    // ✅ FIX 1: markReadFor was missing
    public function markReadFor(User $user): void
    {
        if ($user->id === $this->buyer_id) {
            $this->update(['buyer_unread' => 0]);
        } elseif ($user->id === $this->seller_id) {
            $this->update(['seller_unread' => 0]);
        }
    }

    public function incrementUnreadFor(string $senderId): void
    {
        if ($senderId === $this->buyer_id) {
            $this->increment('seller_unread');
        } else {
            $this->increment('buyer_unread');
        }
    }

    public function messages()  { return $this->hasMany(Message::class); }
    public function buyer()     { return $this->belongsTo(User::class, 'buyer_id'); }
    public function seller()    { return $this->belongsTo(User::class, 'seller_id'); }
    public function shop()      { return $this->belongsTo(Shop::class); }
}

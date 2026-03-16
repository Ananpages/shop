<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

// ==================== WISHLIST ====================
class Wishlist extends Model
{
    use HasUuids;

    protected $table = 'wishlists';
    protected $fillable = ['id', 'user_id', 'product_id'];

    public function user()    { return $this->belongsTo(User::class); }
    public function product() { return $this->belongsTo(Product::class); }
}

// ==================== CONVERSATION ====================
class Conversation extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'buyer_id', 'seller_id', 'shop_id',
        'last_message', 'last_message_at',
        'buyer_unread', 'seller_unread',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'buyer_unread'    => 'integer',
        'seller_unread'   => 'integer',
    ];

    public function buyer()    { return $this->belongsTo(User::class, 'buyer_id'); }
    public function seller()   { return $this->belongsTo(User::class, 'seller_id'); }
    public function shop()     { return $this->belongsTo(Shop::class); }
    public function messages() { return $this->hasMany(Message::class)->orderBy('created_at'); }

    public function getUnreadCountFor(User $user): int
    {
        return $user->id === $this->buyer_id ? $this->buyer_unread : $this->seller_unread;
    }

    public function markReadFor(User $user): void
    {
        $field = $user->id === $this->buyer_id ? 'buyer_unread' : 'seller_unread';
        $this->update([$field => 0]);
    }

    public function incrementUnreadFor(string $recipientId): void
    {
        $field = $recipientId === $this->buyer_id ? 'buyer_unread' : 'seller_unread';
        $this->increment($field);
    }
}

// ==================== MESSAGE ====================
class Message extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'conversation_id', 'sender_id',
        'content', 'type', 'product_id', 'is_read',
    ];

    protected $casts = ['is_read' => 'boolean'];

    public function conversation() { return $this->belongsTo(Conversation::class); }
    public function sender()       { return $this->belongsTo(User::class, 'sender_id'); }
}

// ==================== USER NOTIFICATION ====================
class UserNotification extends Model
{
    use HasUuids;

    protected $table = 'user_notifications';

    protected $fillable = [
        'id', 'user_id', 'title', 'body',
        'type', 'reference_id', 'is_read',
    ];

    protected $casts = ['is_read' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }

    public static function send(string $userId, string $title, string $body, string $type = 'system', ?string $referenceId = null): self
    {
        return self::create([
            'id'           => Str::uuid(),
            'user_id'      => $userId,
            'title'        => $title,
            'body'         => $body,
            'type'         => $type,
            'reference_id' => $referenceId,
        ]);
    }
}

// ==================== RECENTLY VIEWED ====================
class RecentlyViewed extends Model
{
    use HasUuids;

    protected $table = 'recently_viewed';
    protected $fillable = ['id', 'user_id', 'product_id', 'viewed_at'];
    protected $casts = ['viewed_at' => 'datetime'];

    public function user()    { return $this->belongsTo(User::class); }
    public function product() { return $this->belongsTo(Product::class); }
}

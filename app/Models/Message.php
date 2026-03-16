<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'id',
        'conversation_id',
        'sender_id',
        'content',      // ✅ THIS was missing — Laravel silently dropped it
        'type',
        'is_read',
        'meta',
    ];

    protected $keyType   = 'string';
    public $incrementing = false;

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}

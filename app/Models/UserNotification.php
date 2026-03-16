<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserNotification extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'message',
        'type',
        'data',
        'is_read',
    ];

    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

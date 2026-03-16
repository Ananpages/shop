<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrderStatusHistory extends Model
{
    use HasUuids;

    protected $table = 'order_status_history';

    protected $fillable = ['id', 'order_id', 'status', 'note', 'created_by'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

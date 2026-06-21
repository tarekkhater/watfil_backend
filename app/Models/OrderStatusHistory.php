<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}

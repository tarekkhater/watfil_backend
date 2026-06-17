<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderSource extends Model
{
    protected $fillable = [
        'order_id',
        'channel',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WithdrawalAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'withdrawal_request_id',
        'action',
        'actor_type',
        'actor_id',
        'note',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function withdrawalRequest(): BelongsTo
    {
        return $this->belongsTo(WithdrawalRequest::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}

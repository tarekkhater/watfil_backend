<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'direction',
        'category',
        'amount',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'performed_by_type',
        'performed_by_id',
        'idempotency_key',
        'description',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function performedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function meta(): HasMany
    {
        return $this->hasMany(WalletTransactionMeta::class);
    }
}

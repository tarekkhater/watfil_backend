<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommissionEvent extends Model
{
    protected $fillable = [
        'company_id',
        'commission_rule_id',
        'source_type',
        'source_id',
        'gross_amount',
        'commission_amount',
        'net_amount',
        'currency',
        'status',
        'wallet_transaction_id',
        'idempotency_key',
        'processed_at',
    ];

    protected $casts = [
        'gross_amount'      => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount'        => 'decimal:2',
        'processed_at'      => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}

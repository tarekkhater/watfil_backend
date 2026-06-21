<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'company_id',
        'amount',
        'status',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'paid_at',
        'payout_reference',
        'reserved_transaction_id',
        'release_transaction_id',
        'idempotency_key',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at'     => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'approved_by');
    }

    public function reservedTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'reserved_transaction_id');
    }

    public function releaseTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'release_transaction_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(WithdrawalAudit::class);
    }
}

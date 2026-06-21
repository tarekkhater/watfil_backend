<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InstallmentPayment extends Model
{
    protected $fillable = [
        'installment_contract_id',
        'installment_schedule_id',
        'amount',
        'payment_method',
        'recorded_by_type',
        'recorded_by_id',
        'idempotency_key',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(InstallmentContract::class, 'installment_contract_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InstallmentSchedule::class, 'installment_schedule_id');
    }

    public function recordedBy(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'recorded_by_type', 'recorded_by_id');
    }
}

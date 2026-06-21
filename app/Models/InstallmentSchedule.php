<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentSchedule extends Model
{
    protected $table = 'installment_schedule';

    protected $fillable = [
        'installment_contract_id',
        'installment_number',
        'type',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'installment_number' => 'integer',
        'due_date'           => 'date',
        'amount'             => 'decimal:2',
        'paid_amount'        => 'decimal:2',
        'paid_at'            => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(InstallmentContract::class, 'installment_contract_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(InstallmentPenalty::class);
    }

    public function penaltyTotal(): float
    {
        return (float) $this->penalties()->sum('amount');
    }

    public function amountDue(): float
    {
        return round((float) $this->amount + $this->penaltyTotal() - (float) $this->paid_amount, 2);
    }
}

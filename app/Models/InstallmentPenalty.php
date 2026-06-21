<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPenalty extends Model
{
    protected $fillable = [
        'installment_contract_id',
        'installment_schedule_id',
        'amount',
        'reason',
        'applied_at',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(InstallmentContract::class, 'installment_contract_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InstallmentSchedule::class, 'installment_schedule_id');
    }
}

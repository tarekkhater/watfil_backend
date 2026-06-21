<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentContract extends Model
{
    protected $fillable = [
        'order_id',
        'company_id',
        'customer_id',
        'company_product_installment_plan_id',
        'plan_snapshot',
        'principal_amount',
        'down_payment_amount',
        'months',
        'installment_amount',
        'paid_amount',
        'outstanding_amount',
        'status',
        'started_at',
        'completed_at',
        'idempotency_key',
    ];

    protected $casts = [
        'plan_snapshot'      => 'array',
        'principal_amount'     => 'decimal:2',
        'down_payment_amount'  => 'decimal:2',
        'months'               => 'integer',
        'installment_amount'   => 'decimal:2',
        'paid_amount'          => 'decimal:2',
        'outstanding_amount'   => 'decimal:2',
        'started_at'           => 'datetime',
        'completed_at'         => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(CompanyProductInstallmentPlan::class, 'company_product_installment_plan_id');
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(InstallmentSchedule::class)->orderBy('installment_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class)->latest('paid_at');
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(InstallmentPenalty::class)->latest('applied_at');
    }
}

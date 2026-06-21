<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'status',
        'payment_type',
        'installment_plan_id',
        'subtotal',
        'discount',
        'total_amount',
        'governorate_id',
        'notes',
        'idempotency_key',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'discount'     => 'decimal:2',
        'total_amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function source(): HasOne
    {
        return $this->hasOne(OrderSource::class);
    }

    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(CompanyProductInstallmentPlan::class, 'installment_plan_id');
    }

    public function installmentContract(): HasOne
    {
        return $this->hasOne(InstallmentContract::class);
    }
}

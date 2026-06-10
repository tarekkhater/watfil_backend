<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierProduct extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'cash_price',
        'supplier_id',
        'is_active',
    ];

    protected $casts = [
        'cash_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_catalog');
    }

    public function installmentPlans(): HasMany
    {
        return $this->hasMany(SupplierProductInstallmentPlan::class)->orderBy('months');
    }
}

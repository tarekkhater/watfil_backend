<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyProduct extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'cash_price',
        'company_id',
        'category_id',
        'is_active',
    ];

    protected $casts = [
        'cash_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function installmentPlans(): HasMany
    {
        return $this->hasMany(CompanyProductInstallmentPlan::class)->orderBy('months');
    }
}

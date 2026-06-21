<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'parent_category_id',
        'product_type_id',
        'number_of_stages',
    ];

    protected $casts = [
        'number_of_stages' => 'integer',
    ];

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_category_id')->orderBy('name');
    }

    public function companyProducts(): HasMany
    {
        return $this->hasMany(CompanyProduct::class);
    }

    public function supplierProducts(): HasMany
    {
        return $this->hasMany(SupplierProduct::class);
    }
}

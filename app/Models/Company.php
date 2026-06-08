<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class Company extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'tax_number',
        'password',
        'governorate_id',
        'logo',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password'  => 'hashed',
        'is_active' => 'boolean',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(CompanyProduct::class);
    }

    public function catalogProducts(): BelongsToMany
    {
        return $this->belongsToMany(SupplierProduct::class, 'company_catalog');
    }
}

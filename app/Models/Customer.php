<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'phone',
        'password',
        'governorate_id',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password'          => 'hashed',
        'phone_verified_at' => 'datetime',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function companyLikes(): HasMany
    {
        return $this->hasMany(CompanyLike::class);
    }

    public function companyRatings(): HasMany
    {
        return $this->hasMany(CompanyRating::class);
    }
}

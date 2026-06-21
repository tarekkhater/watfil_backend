<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
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
        'wallet_balance',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password'       => 'hashed',
        'is_active'      => 'boolean',
        'wallet_balance' => 'decimal:2',
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

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function commissionEvents(): HasMany
    {
        return $this->hasMany(CommissionEvent::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function customerLinks(): HasMany
    {
        return $this->hasMany(CustomerCompanyLink::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_company_links')
            ->withPivot(['status', 'linked_at'])
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CompanyLike::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(CompanyRating::class);
    }

    public function scopeWithPublicStats(Builder $query, ?int $customerId = null): Builder
    {
        $query->withCount(['likes', 'ratings'])
            ->withAvg('ratings', 'rating');

        if ($customerId) {
            $query->select('companies.*')
                ->withExists(['likes as is_liked' => fn ($q) => $q->where('customer_id', $customerId)])
                ->addSelect([
                    'my_rating' => CompanyRating::query()
                        ->select('rating')
                        ->whereColumn('company_id', 'companies.id')
                        ->where('customer_id', $customerId)
                        ->limit(1),
                ]);
        }

        return $query;
    }
}

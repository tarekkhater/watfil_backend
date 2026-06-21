<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'phone',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password'  => 'hashed',
        'is_active' => 'boolean',
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function companyLinks(): HasMany
    {
        return $this->hasMany(CustomerCompanyLink::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'customer_company_links')
            ->withPivot(['status', 'linked_at'])
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
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

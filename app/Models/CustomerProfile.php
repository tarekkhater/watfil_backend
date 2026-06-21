<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    protected $fillable = [
        'customer_id',
        'full_name',
        'governorate_id',
        'city',
        'address',
        'avatar',
        'date_of_birth',
        'gender',
        'risk_flag',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'risk_flag'     => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}

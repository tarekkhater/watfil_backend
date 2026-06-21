<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRule extends Model
{
    protected $fillable = [
        'name',
        'trigger',
        'calculation_type',
        'amount',
        'priority',
        'is_active',
        'starts_at',
        'ends_at',
        'metadata',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'metadata'  => 'array',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(CommissionEvent::class);
    }
}

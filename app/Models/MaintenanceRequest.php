<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    protected $fillable = [
        'customer_id',
        'company_id',
        'full_name',
        'phone',
        'governorate_id',
        'city',
        'area',
        'address_details',
        'device_details',
        'purification_system',
        'stages_count',
        'last_stage_change_dates',
        'primary_problem_type',
        'malfunction_type',
        'notes',
        'description',
        'address',
        'image',
        'status',
    ];

    protected $casts = [
        'stages_count'            => 'integer',
        'last_stage_change_dates' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}

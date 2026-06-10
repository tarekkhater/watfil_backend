<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProductInstallmentPlan extends Model
{
    public const ALLOWED_MONTHS = [3, 6, 9, 12, 15, 18];

    protected $fillable = [
        'supplier_product_id',
        'months',
        'down_payment',
        'installment_amount',
    ];

    protected $casts = [
        'months'             => 'integer',
        'down_payment'       => 'decimal:2',
        'installment_amount' => 'decimal:2',
    ];

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class);
    }
}

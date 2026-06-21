<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'company_product_id',
        'product_name',
        'quantity',
        'unit_price',
        'line_total',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'metadata'   => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function companyProduct(): BelongsTo
    {
        return $this->belongsTo(CompanyProduct::class);
    }
}

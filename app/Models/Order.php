<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const TYPE_COMPANY_PRODUCT  = 'company_product';
    public const TYPE_SUPPLIER_PRODUCT = 'supplier_product';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'customer_id',
        'company_id',
        'product_type',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'delivery_address',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'unit_price'  => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): ?Model
    {
        return match ($this->product_type) {
            self::TYPE_COMPANY_PRODUCT  => CompanyProduct::find($this->product_id),
            self::TYPE_SUPPLIER_PRODUCT => SupplierProduct::with('supplier')->find($this->product_id),
            default                     => null,
        };
    }
}

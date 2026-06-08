<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'description',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(SupplierProduct::class);
    }
}

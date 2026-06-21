<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    protected $fillable = [
        'name',
        'name_ar',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('name');
    }
}

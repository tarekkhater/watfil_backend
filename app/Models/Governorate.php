<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}

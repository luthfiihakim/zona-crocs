<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Size extends Model
{
    protected $fillable = ['us_label', 'eu_range', 'insole_cm', 'group', 'is_preorder', 'sort_order'];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
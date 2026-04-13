<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariantVariationValue extends Pivot
{
    use SoftDeletes;

    protected $table = 'product_variant_variation_value';

    protected $fillable = [
        'product_variant_id',
        'variation_value_id',
    ];
}

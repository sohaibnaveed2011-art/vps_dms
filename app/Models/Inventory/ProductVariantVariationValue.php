<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $product_variant_id
 * @property int $variation_value_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue whereVariationValueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantVariationValue withoutTrashed()
 * @mixin \Eloquent
 */
class ProductVariantVariationValue extends Pivot
{
    use SoftDeletes;

    protected $table = 'product_variant_variation_value';

    protected $fillable = [
        'product_variant_id',
        'variation_value_id',
    ];
}

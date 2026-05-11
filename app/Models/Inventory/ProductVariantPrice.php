<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $product_variant_id
 * @property string $priceable_type
 * @property int $priceable_id
 * @property numeric|null $cost_price
 * @property numeric|null $sale_price
 * @property bool $is_override
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Organization $organization
 * @property-read Model|\Eloquent $priceable
 * @property-read \App\Models\Inventory\ProductVariant $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice whereCostPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice whereIsOverride($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice wherePriceableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice wherePriceableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantPrice withoutTrashed()
 * @mixin \Eloquent
 */
class ProductVariantPrice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'product_variant_id',
        'priceable_type',
        'priceable_id',
        'cost_price',
        'sale_price',
        'is_override',
    ];

    protected $casts = [
        'cost_price' => 'decimal:6',
        'sale_price' => 'decimal:6',
        'is_override' => 'boolean',
    ];

    /**
     * Get the parent priceable model (Organization, Branch, or StockLocation).
     */
    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
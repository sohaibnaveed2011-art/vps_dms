<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\VariationValue;
use App\Models\Inventory\ProductVariantUnit;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $product_id
 * @property int|null $brand_model_id
 * @property string $sku
 * @property string|null $barcode
 * @property numeric $cost_price
 * @property numeric $sale_price
 * @property bool $is_serial_tracked
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Inventory\BrandModel|null $brandModel
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\CouponTarget> $couponTargets
 * @property-read int|null $coupon_targets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\ProductImage> $images
 * @property-read int|null $images_count
 * @property-read Organization $organization
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariantUnit> $units
 * @property-read int|null $units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, VariationValue> $variationValues
 * @property-read int|null $variation_values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereBrandModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereCostPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereIsSerialTracked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant withoutTrashed()
 * @mixin \Eloquent
 */
class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'product_id',
        'brand_model_id',
        'sku', // special code for internal use
        'barcode', // external code like universal product code (UPC).
        'cost_price',
        'sale_price',
        'is_serial_tracked',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:6',
        'sale_price' => 'decimal:6',
        'is_serial_tracked'=> 'boolean',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function units()
    {
        return $this->hasMany(ProductVariantUnit::class);
    }

    public function variationValues()
    {
        return $this->belongsToMany(
            VariationValue::class,
            'product_variant_variation_value'
        )->withTimestamps();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
    
    public function brandModel(): BelongsTo { return $this->belongsTo(BrandModel::class); }

    public function images(): MorphMany { return $this->morphMany(ProductImage::class, 'imageable')->orderBy('sort_order'); }
    public function couponTargets(): MorphMany
    {
        return $this->morphMany(CouponTarget::class, 'Targetable');
    }
}

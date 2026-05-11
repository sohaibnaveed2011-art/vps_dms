<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use App\Models\Core\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property int|null $brand_model_id
 * @property int|null $tax_id
 * @property int|null $inventory_account_id
 * @property int|null $sale_account_id
 * @property int|null $cogs_account_id
 * @property string $name
 * @property string|null $description
 * @property string $valuation_method
 * @property bool $has_warranty
 * @property int|null $warranty_months
 * @property bool $has_variants
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Inventory\Brand|null $brand
 * @property-read \App\Models\Inventory\BrandModel|null $brandModel
 * @property-read \App\Models\Inventory\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\CouponTarget> $couponTargets
 * @property-read int|null $coupon_targets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\ProductImage> $images
 * @property-read int|null $images_count
 * @property-read Organization $organization
 * @property-read Tax|null $tax
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\ProductVariant> $variants
 * @property-read int|null $variants_count
 * @property-read \App\Models\Inventory\ProductVariation|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\Variation> $variations
 * @property-read int|null $variations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBrandModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCogsAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereHasVariants($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereHasWarranty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereInventoryAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSaleAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereValuationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereWarrantyMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 * @mixin \Eloquent
 */
class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'category_id', 'brand_id', 'brand_model_id', 'tax_id',
        'inventory_account_id', 'sale_account_id', 'cogs_account_id',
        'name', 'description', 'valuation_method',
        'has_warranty', 'warranty_months', 'has_variants', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_warranty' => 'boolean',
        'has_variants' => 'boolean',
    ];

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(Variation::class, 'product_variation', 'product_id', 'variation_id')->using(ProductVariation::class)->withTimestamps();
    }
    public function variants(): HasMany { return $this->hasMany(ProductVariant::class); }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function brandModel(): BelongsTo { return $this->belongsTo(BrandModel::class); }
    public function tax(): BelongsTo { return $this->belongsTo(Tax::class); }
    public function images(): MorphMany { return $this->morphMany(ProductImage::class, 'imageable')->orderBy('sort_order'); }
    public function couponTargets(): MorphMany
    {
        return $this->morphMany(CouponTarget::class, 'Targetable');
    }
}
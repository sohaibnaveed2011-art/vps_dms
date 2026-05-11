<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $brand_id
 * @property string $name
 * @property string|null $series
 * @property string|null $slug
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Inventory\Brand $brand
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\Product> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereSeries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BrandModel withoutTrashed()
 * @mixin \Eloquent
 */
class BrandModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'brand_models';

    protected $fillable = [
        'organization_id',
        'brand_id',
        'name',
        'slug',
        'series',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot: validation, normalization, uniqueness, slug cleanup.
     */
    protected static function booted()
    {
        static::saving(function (BrandModel $model) {
            // 1. Normalize name
            $model->name = trim($model->name);

            // 2. Handle Slug logic
            if (empty($model->slug)) {
                // Generate slug from name if missing
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            } else {
                // Normalize provided slug
                $model->slug = strtolower(trim($model->slug));
            }
        });
    }

    // ----------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------

    /**
     * Organization owner
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Parent Brand
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Items under this model
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_model_id');
    }
}

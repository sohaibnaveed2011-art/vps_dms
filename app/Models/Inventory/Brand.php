<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use App\Models\Inventory\BrandModel;
use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BrandModel> $models
 * @property-read int|null $models_count
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Brand withoutTrashed()
 * @mixin \Eloquent
 */
class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'brands';

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
    ];

    /**
     * Normalize slug and enforce organization integrity.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Brand $brand) {
            // --- Normalize data ---
            $brand->name = trim($brand->name);
            $brand->slug = strtolower(trim($brand->slug ?? ''));

            // --- Basic validation ---
            if (!$brand->organization_id) {
                throw new \InvalidArgumentException('Brand must have an organization_id.');
            }

            if (!$brand->name) {
                throw new \InvalidArgumentException('Brand name cannot be empty.');
            }

            if (!$brand->slug) {
                throw new \InvalidArgumentException('Brand slug cannot be empty.');
            }

            // --- Application-level uniqueness check ---
            // DB already has a unique index: (organization_id, slug)
            $exists = Brand::where('organization_id', $brand->organization_id)
                ->where('slug', $brand->slug)
                ->when($brand->id, fn($q) => $q->where('id', '!=', $brand->id))
                ->exists();

            if ($exists) {
                throw new \InvalidArgumentException(
                    "Brand slug '{$brand->slug}' already exists in this organization."
                );
            }
        });
    }

    // ----------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function models()
    {
        return $this->hasMany(BrandModel::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

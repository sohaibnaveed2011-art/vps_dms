<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $short_name
 * @property bool $is_active
 * @property bool $has_multiple
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\ProductVariation> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\VariationValue> $values
 * @property-read int|null $values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation whereHasMultiple($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation whereShortName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variation withoutTrashed()
 * @mixin \Eloquent
 */
class Variation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'variations';

    protected $fillable = [
        'organization_id',
        'name',
        'short_name',
        'has_multiple',
        'is_active',
    ];

    protected $casts = [
        'has_multiple' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Normalize + validate + enforce uniqueness.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Variation $variation) {

            // -------------------------------------
            // Normalize
            // -------------------------------------
            $variation->name = trim($variation->name);
            $variation->short_name = strtolower(trim($variation->short_name));

            // -------------------------------------
            // Validation
            // -------------------------------------
            if (!$variation->organization_id) {
                throw new \InvalidArgumentException('Variation must have an organization_id.');
            }

            if (!$variation->name) {
                throw new \InvalidArgumentException('Variation name cannot be empty.');
            }

            if (!$variation->short_name) {
                throw new \InvalidArgumentException('Variation short_name cannot be empty.');
            }

            // -------------------------------------
            // Uniqueness per organization
            // unique(['organization_id','short_name'])
            // -------------------------------------
            $exists = Variation::where('organization_id', $variation->organization_id)
                ->where('short_name', $variation->short_name)
                ->when($variation->id, fn($q) => $q->where('id', '!=', $variation->id))
                ->exists();

            if ($exists) {
                throw new \InvalidArgumentException(
                    "Variation short_name '{$variation->short_name}' already exists in this organization."
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

    /**
     * Values that belong to this variation.
     */
    public function values()
    {
        return $this->hasMany(VariationValue::class);
    }

    /**
     * Items associated with this variation.
     * (pivot table product_variation)
     */
    public function products()
    {
        return $this->belongsToMany(ProductVariation::class, 'product_variation');
    }
}

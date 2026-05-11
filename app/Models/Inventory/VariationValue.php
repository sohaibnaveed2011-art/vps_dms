<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $variation_id
 * @property string $value
 * @property string|null $color_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\ProductVariant> $product_variat
 * @property-read int|null $product_variat_count
 * @property-read \App\Models\Inventory\Variation $variation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue whereColorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue whereVariationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationValue withoutTrashed()
 * @mixin \Eloquent
 */
class VariationValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'variation_values';

    protected $fillable = [
        'organization_id',
        'variation_id',
        'value',
        'color_code',
    ];

    protected $casts = [
        'color_code' => 'string',
    ];

    /**
     * Boot: normalize fields, validation, uniqueness.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (VariationValue $valueModel) {

            // -------------------------------------------------
            // Normalize
            // -------------------------------------------------
            $valueModel->value = trim($valueModel->value);

            if ($valueModel->color_code) {
                $valueModel->color_code = strtolower(trim($valueModel->color_code));
            }

            // -------------------------------------------------
            // Validation
            // -------------------------------------------------
            if (! $valueModel->variation_id) {
                throw new \InvalidArgumentException('VariationValue must have a variation_id.');
            }

            if (! $valueModel->value) {
                throw new \InvalidArgumentException('VariationValue value cannot be empty.');
            }

            // -------------------------------------------------
            // Uniqueness
            // unique(['variation_id', 'value'])
            // -------------------------------------------------
            $exists = VariationValue::where('variation_id', $valueModel->variation_id)
                ->where('value', $valueModel->value)
                ->when($valueModel->id, fn ($q) => $q->where('id', '!=', $valueModel->id))
                ->exists();

            if ($exists) {
                throw new \InvalidArgumentException(
                    "Value '{$valueModel->value}' already exists in this variation."
                );
            }
        });
    }

    // ----------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------

    /**
     * Parent Variation: Color / Size / Material etc.
     */
    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    /**
     * Items that use this value (Red / Large / etc.)
     */
    public function product_variat()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_variation_value');
    }
}

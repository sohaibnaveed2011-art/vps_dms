<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

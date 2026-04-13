<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'units';

    protected $fillable = [
        'organization_id',
        'name',
        'short_name',
        'allow_decimal',
    ];

    protected $casts = [
        'allow_decimal' => 'boolean',
    ];

    /**
     * Normalize and validate before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Unit $unit) {
            // ------------------------------
            // Normalize values
            // ------------------------------
            $unit->name = trim($unit->name);
            $unit->short_name = strtolower(trim($unit->short_name));

            // ------------------------------
            // Validation checks
            // ------------------------------
            if (!$unit->organization_id) {
                throw new \InvalidArgumentException('Unit must have an organization_id.');
            }

            if (!$unit->name) {
                throw new \InvalidArgumentException('Unit name cannot be empty.');
            }

            if (!$unit->short_name) {
                throw new \InvalidArgumentException('Unit short_name cannot be empty.');
            }

            // ------------------------------
            // Uniqueness enforcement (before DB exception)
            // unique per organization: (organization_id, short_name)
            // ------------------------------
            $exists = Unit::where('organization_id', $unit->organization_id)
                ->where('short_name', $unit->short_name)
                ->when($unit->id, fn($q) => $q->where('id', '!=', $unit->id))
                ->exists();

            if ($exists) {
                throw new \InvalidArgumentException(
                    "Unit short_name '{$unit->short_name}' already exists in this organization."
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

    public function productVariantUnits()
    {
        return $this->hasMany(ProductVariantUnit::class, 'unit_name', 'name');
    }
}

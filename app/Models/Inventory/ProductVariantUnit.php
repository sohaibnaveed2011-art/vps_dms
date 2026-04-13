<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductVariantUnit extends Model
{
    protected $fillable = [
        'product_variant_id', 'unit_id', 'conversion_factor',
        'is_base', 'is_purchase_unit', 'is_sale_unit', 'is_active',
    ];

    protected $casts = [
        'is_base'          => 'boolean',
        'is_purchase_unit' => 'boolean',
        'is_sale_unit'     => 'boolean',
        'is_active'        => 'boolean',
    ];

    // 2. Add this modern Fluent Attribute
    protected function conversionFactor(): Attribute
    {
        return Attribute::make(
            // When getting the value from the DB, turn it into a string
            get: fn ($value) => $value === null ? null : number_format((float) $value, 6, '.', ''),
            
            // When saving to the DB, force it to a 6-decimal string
            set: fn ($value) => number_format((float) ($value ?? 0), 6, '.', '')
        );
    }

    protected static function booted(): void
    {
        static::saving(function (ProductVariantUnit $model) {
            // Force factor to 1.000000 for base units
            // Force the base unit factor
            if ($model->is_base) {
                // This will now trigger the 'set' logic above safely
                $model->conversion_factor = 1.0; 
            }

            // Ensure no duplicate unit type exists for the same variant
            $duplicate = static::where('product_variant_id', $model->product_variant_id)
                ->where('unit_id', $model->unit_id)
                ->when($model->id, fn($q) => $q->where('id', '!=', $model->id))
                ->exists();

            if ($duplicate) {
                throw new InvalidArgumentException('This unit is already assigned to this variant.');
            }
        });
    }

    public function productVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
}
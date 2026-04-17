<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariantUnit;
use App\Models\Inventory\VariationValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'product_id',
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

    public function images(): MorphMany { return $this->morphMany(ProductImage::class, 'imageable')->orderBy('sort_order'); }
}

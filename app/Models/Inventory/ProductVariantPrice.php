<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariantPrice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'product_variant_id',
        'priceable_type',
        'priceable_id',
        'cost_price',
        'sale_price',
        'is_override',
    ];

    protected $casts = [
        'cost_price' => 'decimal:6',
        'sale_price' => 'decimal:6',
        'is_override' => 'boolean',
    ];

    /**
     * Get the parent priceable model (Organization, Branch, or StockLocation).
     */
    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
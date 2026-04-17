<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariantDiscount extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'product_variant_id',
        'discountable_type',
        'discountable_id',
        'type',
        'priority',
        'stackable',
        'max_discount_amount',
        'value',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
        'value'      => 'decimal:6',
        'priority'   => 'integer',
        'stackable'  => 'boolean',
    ];

    /**
     * Get the parent discountable model.
     */
    public function discountable(): MorphTo
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
    
    /**
     * Scope to only include active discounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
                     ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }
}
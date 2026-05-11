<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $product_variant_id
 * @property string $discountable_type
 * @property int $discountable_id
 * @property string $application_type
 * @property string $discount_type
 * @property numeric $value
 * @property numeric|null $max_discount_amount
 * @property int $priority
 * @property bool $stackable
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Model|\Eloquent $discountable
 * @property-read string $formatted_discount
 * @property-read Organization $organization
 * @property-read \App\Models\Inventory\ProductVariant $variant
 * @method static Builder<static>|ProductVariantDiscount active()
 * @method static Builder<static>|ProductVariantDiscount costDiscounts()
 * @method static Builder<static>|ProductVariantDiscount forApplication(string $applicationType)
 * @method static Builder<static>|ProductVariantDiscount forScope(string $scopeType, int $scopeId)
 * @method static Builder<static>|ProductVariantDiscount newModelQuery()
 * @method static Builder<static>|ProductVariantDiscount newQuery()
 * @method static Builder<static>|ProductVariantDiscount ofType(string $discountType)
 * @method static Builder<static>|ProductVariantDiscount onlyTrashed()
 * @method static Builder<static>|ProductVariantDiscount orderedByPriority()
 * @method static Builder<static>|ProductVariantDiscount query()
 * @method static Builder<static>|ProductVariantDiscount saleDiscounts()
 * @method static Builder<static>|ProductVariantDiscount whereApplicationType($value)
 * @method static Builder<static>|ProductVariantDiscount whereCreatedAt($value)
 * @method static Builder<static>|ProductVariantDiscount whereDeletedAt($value)
 * @method static Builder<static>|ProductVariantDiscount whereDiscountType($value)
 * @method static Builder<static>|ProductVariantDiscount whereDiscountableId($value)
 * @method static Builder<static>|ProductVariantDiscount whereDiscountableType($value)
 * @method static Builder<static>|ProductVariantDiscount whereEndDate($value)
 * @method static Builder<static>|ProductVariantDiscount whereId($value)
 * @method static Builder<static>|ProductVariantDiscount whereIsActive($value)
 * @method static Builder<static>|ProductVariantDiscount whereMaxDiscountAmount($value)
 * @method static Builder<static>|ProductVariantDiscount whereOrganizationId($value)
 * @method static Builder<static>|ProductVariantDiscount wherePriority($value)
 * @method static Builder<static>|ProductVariantDiscount whereProductVariantId($value)
 * @method static Builder<static>|ProductVariantDiscount whereStackable($value)
 * @method static Builder<static>|ProductVariantDiscount whereStartDate($value)
 * @method static Builder<static>|ProductVariantDiscount whereUpdatedAt($value)
 * @method static Builder<static>|ProductVariantDiscount whereValue($value)
 * @method static Builder<static>|ProductVariantDiscount withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|ProductVariantDiscount withoutTrashed()
 * @mixin \Eloquent
 */
class ProductVariantDiscount extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'organization_id',
        'product_variant_id',
        'discountable_type',
        'discountable_id',
        'application_type',
        'discount_type',
        'value',
        'max_discount_amount',
        'priority',
        'stackable',
        'start_date',
        'end_date',
        'is_active',
    ];
    
    protected $casts = [
        'value' => 'decimal:6',
        'max_discount_amount' => 'decimal:6',
        'priority' => 'integer',
        'stackable' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Constants for application types
    const APPLICATION_SALE = 'sale';
    const APPLICATION_COST = 'cost';
    
    // Constants for discount types
    const DISCOUNT_PERCENTAGE = 'percentage';
    const DISCOUNT_FIXED = 'fixed';

    /**
     * Get available application types
     */
    public static function getApplicationTypes(): array
    {
        return [
            self::APPLICATION_SALE => 'Sale Discount',
            self::APPLICATION_COST => 'Cost Discount',
        ];
    }

    /**
     * Get available discount types
     */
    public static function getDiscountTypes(): array
    {
        return [
            self::DISCOUNT_PERCENTAGE => 'Percentage (%)',
            self::DISCOUNT_FIXED => 'Fixed Amount',
        ];
    }

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
     * Scope for sale discounts only
     */
    public function scopeSaleDiscounts(Builder $query): Builder
    {
        return $query->where('application_type', self::APPLICATION_SALE);
    }
    
    /**
     * Scope for cost discounts only
     */
    public function scopeCostDiscounts(Builder $query): Builder
    {
        return $query->where('application_type', self::APPLICATION_COST);
    }
    
    /**
     * Scope to only include active discounts
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function(Builder $q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function(Builder $q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }
    
    /**
     * Scope ordered by priority (highest first)
     */
    public function scopeOrderedByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc');
    }
    
    /**
     * Scope to filter by application type
     */
    public function scopeForApplication(Builder $query, string $applicationType): Builder
    {
        return $query->where('application_type', $applicationType);
    }
    
    /**
     * Scope to filter by discount type
     */
    public function scopeOfType(Builder $query, string $discountType): Builder
    {
        return $query->where('discount_type', $discountType);
    }
    
    /**
     * Scope for discounts that apply to a specific scope
     */
    public function scopeForScope(Builder $query, string $scopeType, int $scopeId): Builder
    {
        return $query->where('discountable_type', $scopeType)
            ->where('discountable_id', $scopeId);
    }
    
    /**
     * Check if discount is currently active
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->start_date && now()->lt($this->start_date)) {
            return false;
        }
        
        if ($this->end_date && now()->gt($this->end_date)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculate discount amount based on base price
     */
    public function calculateDiscount(float $basePrice): float
    {
        if (!$this->isActive()) {
            return 0;
        }
        
        $discountAmount = match($this->discount_type) {
            self::DISCOUNT_PERCENTAGE => $basePrice * ((float) $this->value / 100),
            self::DISCOUNT_FIXED => (float) $this->value,
            default => 0,
        };
        
        // Apply max discount cap if set
        if ($this->max_discount_amount && $discountAmount > (float) $this->max_discount_amount) {
            $discountAmount = (float) $this->max_discount_amount;
        }
        
        // Don't discount below zero
        return min($discountAmount, $basePrice);
    }
    
    /**
     * Get the effective price after discount
     */
    public function getEffectivePrice(float $basePrice): float
    {
        $discount = $this->calculateDiscount($basePrice);
        return max(0, $basePrice - $discount);
    }
    
    /**
     * Get formatted discount value with type
     */
    public function getFormattedDiscountAttribute(): string
    {
        if ($this->discount_type === self::DISCOUNT_PERCENTAGE) {
            return number_format((float) $this->value, 2) . '%';
        }
        
        // For fixed amount, format as currency
        return number_format((float) $this->value, 2);
    }
    
    /**
     * Get discount amount preview based on a sample price
     */
    public function getDiscountPreview(float $samplePrice): array
    {
        $discountAmount = $this->calculateDiscount($samplePrice);
        
        return [
            'original_price' => $samplePrice,
            'discount_amount' => $discountAmount,
            'effective_price' => $samplePrice - $discountAmount,
            'discount_percentage' => $samplePrice > 0 ? ($discountAmount / $samplePrice) * 100 : 0,
        ];
    }
    
    /**
     * Check if this discount applies to sale price
     */
    public function appliesToSale(): bool
    {
        return $this->application_type === self::APPLICATION_SALE;
    }
    
    /**
     * Check if this discount applies to cost price
     */
    public function appliesToCost(): bool
    {
        return $this->application_type === self::APPLICATION_COST;
    }
    
    /**
     * Check if this discount is stackable with others
     */
    public function isStackable(): bool
    {
        return $this->stackable;
    }
}
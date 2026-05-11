<?php

namespace App\Models\Inventory;

use App\Models\Partner\Customer;
use App\Models\Inventory\CouponScope;
use App\Models\Inventory\CouponTarget;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\CustomerCoupon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $code
 * @property string|null $name
 * @property string|null $description
 * @property string $type
 * @property numeric $value
 * @property numeric|null $min_order_amount
 * @property numeric $max_discount
 * @property \Illuminate\Support\Carbon|null $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_to
 * @property int|null $usage_limit
 * @property int|null $usage_limit_per_customer
 * @property int $used_count
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CustomerCoupon> $customerCoupons
 * @property-read int|null $customer_coupons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Customer> $customers
 * @property-read int|null $customers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CouponScope> $scopes
 * @property-read int|null $scopes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CouponTarget> $targets
 * @property-read int|null $targets_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereMaxDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereMinOrderAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUsageLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUsageLimitPerCustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUsedCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereValidTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon withoutTrashed()
 * @mixin \Eloquent
 */
class Coupon extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'valid_from',
        'valid_to',
        'usage_limit',
        'usage_limit_per_customer', // NEW
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'is_active' => 'boolean',
        'used_count' => 'integer',
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'usage_limit_per_customer' => 'integer',
    ];

    public function scopes(): HasMany
    {
        return $this->hasMany(CouponScope::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(CouponTarget::class);
    }

    public function customerCoupons(): HasMany
    {
        return $this->hasMany(CustomerCoupon::class);
    }
    
    // Helper to get assigned customers
    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_coupons')
                    ->withPivot('used_at', 'used_count')
                    ->withTimestamps();
    }
    
    public function isValid()
    {
        return $this->is_active &&
            now()->between($this->valid_from, $this->valid_to) &&
            ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }
    
    // Check if coupon is valid for specific customer
    public function isValidForCustomer(int $customerId): bool
    {
        // Check if coupon has customer restrictions
        $customerCoupon = $this->customerCoupons()
            ->where('customer_id', $customerId)
            ->first();
        
        if ($this->customerCoupons()->exists()) {
            // Coupon is only for specific customers
            if (!$customerCoupon) {
                return false;
            }
            
            // Check per-customer usage limit
            if ($this->usage_limit_per_customer && 
                $customerCoupon->used_count >= $this->usage_limit_per_customer) {
                return false;
            }
        }
        
        return $this->isValid();
    }
}
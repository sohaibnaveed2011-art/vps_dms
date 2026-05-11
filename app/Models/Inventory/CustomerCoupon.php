<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Coupon;
use App\Models\Partner\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $coupon_id
 * @property bool $is_used
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property int $used_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Coupon $coupon
 * @property-read Customer $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon whereIsUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon whereUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon whereUsedCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCoupon withoutTrashed()
 * @mixin \Eloquent
 */
class CustomerCoupon extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'coupon_id',
        'is_used',
        'used_at',
        'used_count',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'used_count' => 'integer',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

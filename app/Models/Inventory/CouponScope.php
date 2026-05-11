<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $coupon_id
 * @property string $scopeable_type
 * @property int $scopeable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Coupon $coupon
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope able()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope whereScopeableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope whereScopeableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponScope withoutTrashed()
 * @mixin \Eloquent
 */
class CouponScope extends Model
{
    use SoftDeletes;
    protected $table = "coupon_scopes";
    protected $fillable = [
        'coupon_id',
        'scopeable_type',
        'scopeable_id',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopeable(): MorphTo
    {
        return $this->morphTo();
    }
}

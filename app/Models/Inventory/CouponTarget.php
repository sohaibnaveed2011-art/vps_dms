<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $coupon_id
 * @property string $targetable_type
 * @property int $targetable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Coupon $coupon
 * @property-read Model|\Eloquent $targetable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget whereTargetableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget whereTargetableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponTarget withoutTrashed()
 * @mixin \Eloquent
 */
class CouponTarget extends Model
{
    use SoftDeletes;
    protected $table = "coupon_targets";
    protected $fillable = [
        'coupon_id',
        'targetable_type',
        'targetable_id',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }
}

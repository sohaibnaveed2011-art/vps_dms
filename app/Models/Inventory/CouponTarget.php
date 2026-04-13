<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponTarget extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'coupon_id',
        'targetable_type',
        'targetable_id',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function targetable()
    {
        return $this->morphTo();
    }
}

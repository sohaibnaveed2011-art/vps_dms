<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponScope extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'coupon_id',
        'scopeable_type',
        'scopeable_id',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopeable()
    {
        return $this->morphTo();
    }
}

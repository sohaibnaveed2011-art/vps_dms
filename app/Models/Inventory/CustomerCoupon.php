<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Coupon;
use App\Models\Partner\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCoupon extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'coupon_id',
        'is_used',
        'used_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
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

<?php
namespace App\Models\Inventory;
use App\Models\Inventory\CouponScope;
use App\Models\Inventory\CouponTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'code',
        'type',
        'value',
        'min_order_amount',
        'valid_from',
        'valid_to',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    public function scopes()
    {
        return $this->morphMany(CouponScope::class, 'scopeable');
    }

    public function targets()
    {
        return $this->morphMany(CouponTarget::class, 'targetable');
    }
    public function isValid()
    {
        return $this->is_active &&
            now()->between($this->valid_from, $this->valid_to);
    }
}

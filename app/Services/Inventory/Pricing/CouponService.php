<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\Coupon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function paginate(int $organizationId, int $perPage)
    {
        return Coupon::where('organization_id', $organizationId)
            ->paginate($perPage);
    }

    public function create(int $organizationId, array $data)
    {
        $data['organization_id'] = $organizationId;
        return Coupon::create($data);
    }

    public function find(int $organizationId, int $id)
    {
        return Coupon::where('organization_id', $organizationId)
            ->findOrFail($id);
    }

    public function update(Coupon $coupon, array $data)
    {
        $coupon->update($data);
        return $coupon;
    }

    public function delete(Coupon $coupon)
    {
        $coupon->delete();
    }

    public function apply(int $organizationId, array $data)
    {
        $coupon = Coupon::where('organization_id', $organizationId)
            ->where('code', $data['code'])
            ->firstOrFail();

        if (!$coupon->is_active) {
            throw ValidationException::withMessages(['code' => 'Coupon inactive']);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages(['code' => 'Usage limit exceeded']);
        }

        $subtotal = $data['subtotal'];

        $discount = $coupon->type === 'percentage'
            ? $subtotal * ($coupon->value / 100)
            : $coupon->value;

        return [
            'coupon_id' => $coupon->id,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
        ];
    }
}

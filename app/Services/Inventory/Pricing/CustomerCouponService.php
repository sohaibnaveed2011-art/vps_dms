<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\CustomerCoupon;

class CustomerCouponService
{
    public function assign(int $organizationId, array $data)
    {
        return CustomerCoupon::create($data);
    }
}

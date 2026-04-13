<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\CouponTarget;

class CouponTargetService
{
    public function create(int $organizationId, array $data)
    {
        return CouponTarget::create($data);
    }

    public function delete(int $id)
    {
        CouponTarget::findOrFail($id)->delete();
    }
}

<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\CouponScope;

class CouponScopeService
{
    public function create(int $organizationId, array $data)
    {
        return CouponScope::create($data);
    }

    public function delete(int $id)
    {
        CouponScope::findOrFail($id)->delete();
    }
}

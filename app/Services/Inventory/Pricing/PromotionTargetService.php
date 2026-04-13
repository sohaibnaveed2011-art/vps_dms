<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\PromotionTarget;

class PromotionTargetService
{
    public function create(int $organizationId, array $data)
    {
        return PromotionTarget::create($data);
    }

    public function delete(int $id)
    {
        PromotionTarget::findOrFail($id)->delete();
    }
}

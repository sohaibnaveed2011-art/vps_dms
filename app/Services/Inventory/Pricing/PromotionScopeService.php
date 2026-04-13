<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\PromotionScope;

class PromotionScopeService
{
    public function create(int $organizationId, array $data)
    {
        return PromotionScope::create($data);
    }

    public function delete(int $id)
    {
        PromotionScope::findOrFail($id)->delete();
    }
}

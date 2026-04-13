<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\Promotion;

class PromotionService
{
    public function paginate(int $organizationId, array $filters, int $perPage)
    {
        return Promotion::query()
            ->where('organization_id', $organizationId)
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where('name', 'like', '%' . trim($filters['search']) . '%')
            )
            ->paginate($perPage);
    }

    public function create(int $organizationId, array $data)
    {
        $data['organization_id'] = $organizationId;
        return Promotion::create($data);
    }

    public function find(int $organizationId, int $id)
    {
        return Promotion::where('organization_id', $organizationId)
            ->findOrFail($id);
    }

    public function update(Promotion $promotion, array $data)
    {
        $promotion->update($data);
        return $promotion;
    }

    public function delete(Promotion $promotion)
    {
        $promotion->delete();
    }
}

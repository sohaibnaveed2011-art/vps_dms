<?php

namespace App\Services\Inventory\Pricing;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\PriceListItem;

class PriceListItemService
{
    public function paginate(array $filters, int $perPage)
    {
        return PriceListItem::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%{$filters['search']}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): PriceListItem
    {
        $query = PriceListItem::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $priceListItem = $query->find($id);

        if (!$priceListItem) {
            throw new NotFoundException('Price List not found.');
        }

        return $priceListItem;
    }

    public function create(array $data)
    {
        return PriceListItem::create($data);
    }

    public function update(PriceListItem $priceListItem, array $data)
    {
        $priceListItem->update($data);
        return $priceListItem;
    }

    public function delete(PriceListItem $priceListItem): void
    {
        $priceListItem->delete();
    }

    public function restore(PriceListItem $priceListItem): void 
    {
        if (!$priceListItem->trashed()) {
            throw new NotFoundException('Price List Item is not deleted.');
        }
        $priceListItem->restore();
    }

    public function forceDelete(PriceListItem $priceListItem): void
    {
        $priceListItem->forceDelete();
    }
}

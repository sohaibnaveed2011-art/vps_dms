<?php

namespace App\Services\Inventory\Pricing;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\PriceList;

class PriceListService
{
    public function paginate(array $filters, int $perPage)
    {
        return PriceList::query()
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

    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): PriceList
    {
        $query = PriceList::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $priceList = $query->find($id);

        if (!$priceList) {
            throw new NotFoundException('Price List not found.');
        }

        return $priceList;
    }

    public function create(array $data)
    {
        return PriceList::create($data);
    }

    public function update(PriceList $priceList, array $data)
    {
        $priceList->update($data);
        return $priceList;
    }

    public function delete(PriceList $priceList): void
    {
        $priceList->delete();
    }

    public function restore(PriceList $priceList): void 
    {
        if (!$priceList->trashed()) {
            throw new NotFoundException('Price List is not deleted.');
        }
        $priceList->restore();
    }

    public function forceDelete(PriceList $priceList): void
    {
        $priceList->forceDelete();
    }
}

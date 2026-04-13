<?php

namespace App\Services\Inventory;

use App\Models\Inventory\StockLocation;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryLedger;
use App\Models\Inventory\InventoryReservation;

class StockLocationService
{
    public function paginate(int $organizationId, array $filters, int $perPage)
    {
        return StockLocation::query()
            ->where('organization_id', $organizationId)
            ->with('locatable')
            ->when(isset($filters['search']), fn($q) =>
                $q->where('name','like',"%{$filters['search']}%")
            )
            ->paginate($perPage);
    }

    public function create(array $data)
    {
        return StockLocation::create($data);
    }

    public function find(int $organizationId, int $id)
    {
        return StockLocation::where('organization_id',$organizationId)
            ->where('id',$id)
            ->with('locatable')
            ->first();
    }

    public function findWithTrashed(int $organizationId, int $id)
    {
        return StockLocation::withTrashed()
            ->where('organization_id',$organizationId)
            ->where('id',$id)
            ->first();
    }

    public function update(StockLocation $location, array $data)
    {
        $location->update($data);
        return $location;
    }

    public function delete(StockLocation $location)
    {
        $location->delete();
    }

    public function restore(StockLocation $location)
    {
        $location->restore();
    }

    public function forceDelete(StockLocation $location)
    {
        $hasBalance = InventoryBalance::where(
            'stock_location_id',$location->id
        )->exists();

        $hasLedger = InventoryLedger::where(
            'stock_location_id',$location->id
        )->exists();

        $hasReservation = InventoryReservation::where(
            'stock_location_id',$location->id
        )->exists();

        if ($hasBalance || $hasLedger || $hasReservation) {
            throw new \Exception(
                'Cannot permanently delete location with inventory history.'
            );
        }

        $location->forceDelete();
    }
}

<?php

namespace App\Services\Inventory\Delivery;

use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\SerialNumber;
use RuntimeException;

class RiderAccountabilityService
{
    public function validateRiderStock(
        int $riderLocationId,
        int $variantId,
        float $quantity
    ): void {

        $available = InventoryBalance::query()
            ->where('stock_location_id', $riderLocationId)
            ->where('product_variant_id', $variantId)
            ->sum('quantity');

        if ($available < $quantity) {
            throw new RuntimeException("Rider stock insufficient.");
        }
    }

    public function validateSerialOwnership(
        int $riderLocationId,
        array $serialIds
    ): void {

        $count = SerialNumber::query()
            ->whereIn('id', $serialIds)
            ->where('stock_location_id', $riderLocationId)
            ->count();

        if ($count !== count($serialIds)) {
            throw new RuntimeException("Serial mismatch for rider.");
        }
    }
}

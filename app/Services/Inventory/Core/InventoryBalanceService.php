<?php

namespace App\Services\Inventory\Core;

use App\Models\Inventory\InventoryBalance;
use RuntimeException;

class InventoryBalanceService
{
    protected int $scale = 6;

    public function adjust(
        int $organizationId,
        int $stockLocationId,
        int $variantId,
        ?int $batchId,
        int $conditionId,
        float $quantity,
        float $unitCost
    ): void {

        $balance = InventoryBalance::query()
            ->where('organization_id', $organizationId)
            ->where('stock_location_id', $stockLocationId)
            ->where('product_variant_id', $variantId)
            ->where('inventory_batch_id', $batchId)
            ->where('condition_id', $conditionId)
            ->lockForUpdate()
            ->first();

        if (! $balance) {
            $balance = InventoryBalance::create([
                'organization_id'    => $organizationId,
                'stock_location_id'  => $stockLocationId,
                'product_variant_id' => $variantId,
                'inventory_batch_id' => $batchId,
                'condition_id'       => $conditionId,
                'quantity'           => 0,
                'reserved_quantity'  => 0,
                'min_stock'          => 0,
                'reorder_point'      => 0,
                'avg_cost'           => 0,
            ]);
        }

        $currentQty = (float) $balance->quantity;
        $newQty     = round($currentQty + $quantity, $this->scale);

        if ($newQty < 0) {
            throw new RuntimeException("Insufficient stock.");
        }

        /*
        |--------------------------------------------------------------------------
        | Weighted Average Calculation
        |--------------------------------------------------------------------------
        | Only apply when:
        | - inbound movement
        | - no batch (WAVG mode)
        | - condition is sellable (optional business rule)
        */
        if ($quantity > 0 && $batchId === null) {

            $currentAvg = (float) $balance->avg_cost;

            $newAvg = $currentQty > 0
                ? (($currentQty * $currentAvg) + ($quantity * $unitCost))
                    / ($currentQty + $quantity)
                : $unitCost;

            $balance->avg_cost = number_format($newAvg, $this->scale, '.', '');
        }

        $balance->quantity = number_format($newQty, $this->scale, '.', '');

        $balance->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Reservation Adjustment
    |--------------------------------------------------------------------------
    */

    public function adjustReserved(
        int $organizationId,
        int $stockLocationId,
        int $variantId,
        ?int $batchId,
        int $conditionId,
        float $quantity
    ): void {

        $balance = InventoryBalance::query()
            ->where('organization_id', $organizationId)
            ->where('stock_location_id', $stockLocationId)
            ->where('product_variant_id', $variantId)
            ->where('inventory_batch_id', $batchId)
            ->where('condition_id', $conditionId)
            ->lockForUpdate()
            ->first();

        if (! $balance) {
            throw new RuntimeException("No balance found for reservation.");
        }

        $newReserved = round(
            (float)$balance->reserved_quantity + $quantity,
            $this->scale
        );

        if ($newReserved < 0) {
            throw new RuntimeException("Invalid reservation adjustment.");
        }

        if ($newReserved > $balance->quantity) {
            throw new RuntimeException("Cannot reserve more than available stock.");
        }

        $balance->reserved_quantity = number_format($newReserved, $this->scale, '.', '');
        $balance->save();
    }
}

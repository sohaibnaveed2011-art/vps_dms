<?php

namespace App\Services\Inventory\Core;

use Illuminate\Support\Facades\DB;

class StockMovementService
{
    public function __construct(
        protected InventoryLedgerService $ledger,
        protected InventoryBalanceService $balance
    ) {}

    public function move(array $payload): void
    {
        DB::transaction(function () use ($payload) {

            $organizationId  = $payload['organization_id'];
            $locationId      = $payload['stock_location_id'];
            $variantId       = $payload['product_variant_id'];
            $batchId         = $payload['inventory_batch_id'] ?? null;
            $conditionId     = $payload['condition_id'];
            $quantity        = (float) $payload['quantity'];
            $unitCost        = (float) $payload['unit_cost'];
            $transactionType = $payload['transaction_type'] ?? 'system';

            $quantityIn  = $quantity > 0 ? $quantity : 0;
            $quantityOut = $quantity < 0 ? abs($quantity) : 0;

            $formattedUnitCost  = number_format($unitCost, 6, '.', '');
            $formattedTotalCost = number_format($unitCost * abs($quantity), 6, '.', '');

            // 1️⃣ Immutable Ledger Entry
            $this->ledger->record([
                'organization_id'    => $organizationId,
                'stock_location_id'  => $locationId,
                'product_variant_id' => $variantId,
                'inventory_batch_id' => $batchId,
                'condition_id'       => $conditionId,
                'reference_type'     => $payload['reference_type'] ?? null,
                'reference_id'       => $payload['reference_id'] ?? null,
                'transaction_type'   => $transactionType,
                'quantity_in'        => $quantityIn,
                'quantity_out'       => $quantityOut,
                'unit_cost'          => $formattedUnitCost,
                'total_cost'         => $formattedTotalCost,
                'created_by'         => $payload['created_by'] ?? null,
            ]);

            // 2️⃣ Update Balance
            $this->balance->adjust(
                $organizationId,
                $locationId,
                $variantId,
                $batchId,
                $conditionId,
                $quantity,
                $unitCost
            );

        });
    }
}

<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryBalance;
use Illuminate\Support\Facades\DB;

class PurchaseStockEngine
{
    public function __construct(
        protected StockMovementService $movementService
    ) {}

    public function add(array $payload): void
    {
        DB::transaction(function () use ($payload) {

            $organizationId = $payload['organization_id'];
            $locationId     = $payload['stock_location_id'];
            $variantId      = $payload['product_variant_id'];
            $quantity       = $payload['quantity'];
            $unitCost       = $payload['unit_cost'];

            /*
            |--------------------------------------------------------------------------
            | 1. Create Inventory Batch
            |--------------------------------------------------------------------------
            */

            $batch = InventoryBatch::create([
                'product_variant_id' => $variantId,
                'batch_number'       => $payload['batch_number'],
                'manufacturing_date' => $payload['manufacturing_date'] ?? null,
                'expiry_date'        => $payload['expiry_date'] ?? null,
                'initial_cost'       => $unitCost,
                'remaining_quantity' => $quantity,
                'mrp'                => $payload['mrp'] ?? null,
                'warranty_months'    => $payload['warranty_months'] ?? null,
                'status'             => 'open',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 2. Create Ledger Entry
            |--------------------------------------------------------------------------
            */

            $this->movementService->move([
                'organization_id'     => $organizationId,
                'stock_location_id'   => $locationId,
                'product_variant_id'  => $variantId,
                'inventory_batch_id'  => $batch->id,
                'quantity'            => $quantity,
                'unit_cost'           => $unitCost,
                'transaction_type'    => $payload['transaction_type'],
                'reference_type'      => $payload['reference_type'] ?? null,
                'reference_id'        => $payload['reference_id'] ?? null,
                'created_by'          => $payload['created_by'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 3. Update Weighted Average
            |--------------------------------------------------------------------------
            */

            $balance = InventoryBalance::firstOrCreate([
                'organization_id'    => $organizationId,
                'stock_location_id'  => $locationId,
                'product_variant_id' => $variantId,
                'inventory_batch_id' => null,
            ], [
                'quantity' => 0,
                'avg_cost' => 0,
            ]);

            $oldQty = $balance->quantity;
            $oldAvg = $balance->avg_cost;

            $newQty = $oldQty + $quantity;

            $newAvg = $newQty == 0
                ? 0
                : (($oldQty * $oldAvg) + ($quantity * $unitCost)) / $newQty;

            $balance->update([
                'quantity' => $newQty,
                'avg_cost' => $newAvg,
            ]);
        });
    }
}

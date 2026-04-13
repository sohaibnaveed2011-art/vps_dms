<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProductVariant;
use RuntimeException;
use Illuminate\Support\Facades\DB;

class InboundStockEngine
{
    public function __construct(
        protected InventoryBatchService $batchService,
        protected StockMovementService $movementService
    ) {}

    public function add(array $payload): void
    {
        DB::transaction(function () use ($payload) {

            $variant = ProductVariant::query()
                ->lockForUpdate()
                ->findOrFail($payload['product_variant_id']);

            $variant->loadMissing('product');

            $valuation = $variant->product->valuation_method;

            $organizationId = $variant->organization_id;
            $locationId     = $payload['stock_location_id'];
            $quantity       = (float) $payload['quantity'];
            $unitCost       = (float) $payload['unit_cost'];

            if ($quantity <= 0) {
                throw new RuntimeException("Inbound quantity must be positive.");
            }

            // ===============================================
            // FIFO / FEFO → Batch-based inbound
            // ===============================================
            if (in_array($valuation, ['FIFO', 'FEFO'])) {

                $batch = $this->batchService->create([
                    'product_variant_id' => $variant->id,
                    'batch_number'       => $payload['batch_number'] ?? uniqid('BATCH-'),
                    'manufacturing_date' => $payload['manufacturing_date'] ?? null,
                    'expiry_date'        => $payload['expiry_date'] ?? null,
                    'initial_cost'       => $unitCost,
                ]);

                $this->movementService->move([
                    ...$payload,
                    'organization_id'    => $organizationId,
                    'product_variant_id' => $variant->id,
                    'inventory_batch_id' => $batch->id,
                    'quantity'           => $quantity,
                    'unit_cost'          => $unitCost,
                ]);

                return;
            }

            // ===============================================
            // WAVG → No batch
            // ===============================================
            if ($valuation === 'WAVG') {

                $this->movementService->move([
                    ...$payload,
                    'organization_id'    => $organizationId,
                    'product_variant_id' => $variant->id,
                    'inventory_batch_id' => null,
                    'quantity'           => $quantity,
                    'unit_cost'          => $unitCost,
                ]);

                return;
            }

            throw new RuntimeException("Unsupported valuation method.");
        });
    }
}

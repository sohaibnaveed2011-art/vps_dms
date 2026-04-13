<?php

namespace App\Services\Inventory\Valuation;

use App\Models\Inventory\ProductVariant;
use App\Services\Inventory\Contracts\ValuationStrategyInterface;
use App\Services\Inventory\InventoryBatchService;
use App\Services\Inventory\StockMovementService;
use RuntimeException;

class FefoInventoryEngine implements ValuationStrategyInterface
{
    public function __construct(
        protected InventoryBatchService $batchService,
        protected StockMovementService $movementService
    ) {}

    public function consume(array $payload, ProductVariant $variant): void
    {
        $locationId = $payload['stock_location_id'];
        $required   = abs($payload['quantity']);

        if ($required <= 0) {
            throw new RuntimeException("Invalid quantity.");
        }

        $batches = $this->batchService
            ->getAvailableBatchesFEFO($variant->id, $locationId);

        if ($batches->isEmpty()) {
            throw new RuntimeException("No stock available.");
        }

        foreach ($batches as $batch) {

            if ($required <= 0) {
                break;
            }

            $balance = $batch->balances
                ->firstWhere('stock_location_id', $locationId);

            $available = (float) ($balance?->quantity ?? 0);

            if ($available <= 0) {
                continue;
            }

            $deduct = min($available, $required);

            $this->movementService->move([
                ...$payload,
                'inventory_batch_id' => $batch->id,
                'quantity'           => -$deduct,
                'unit_cost'          => (float) $batch->initial_cost,
            ]);

            $required -= $deduct;
        }

        if ($required > 0) {
            throw new RuntimeException("Insufficient stock.");
        }
    }
}

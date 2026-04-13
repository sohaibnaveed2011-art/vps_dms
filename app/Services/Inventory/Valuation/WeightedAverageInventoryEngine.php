<?php

namespace App\Services\Inventory\Valuation;

use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\ProductVariant;
use App\Services\Inventory\Contracts\ValuationStrategyInterface;
use App\Services\Inventory\StockMovementService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WeightedAverageInventoryEngine implements ValuationStrategyInterface
{
    public function __construct(
        protected StockMovementService $movementService
    ) {}

    public function consume(array $payload, ProductVariant $variant): void
    {
        DB::transaction(function () use ($payload, $variant) {

            $locationId  = $payload['stock_location_id'];
            $requiredQty = abs($payload['quantity']);

            if ($requiredQty <= 0) {
                throw new RuntimeException("Invalid quantity.");
            }

            $balance = InventoryBalance::query()
                ->where('organization_id', $variant->organization_id)
                ->where('stock_location_id', $locationId)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $balance || $balance->quantity < $requiredQty) {
                throw new RuntimeException("Insufficient stock.");
            }

            $avgCost = $balance->avg_cost;

            $this->movementService->move([
                ...$payload,
                'organization_id'   => $variant->organization_id,
                'product_variant_id'=> $variant->id,
                'inventory_batch_id'=> null,
                'quantity'          => -$requiredQty,
                'unit_cost'         => $avgCost,
            ]);
        });
    }
}

<?php

namespace App\Services\Inventory\Valuation;

use App\Models\Inventory\ProductVariant;
use App\Services\Inventory\Contracts\ValuationStrategyInterface;
use App\Services\Inventory\Valuation\FefoInventoryEngine;
use App\Services\Inventory\Valuation\FifoInventoryEngine;
use App\Services\Inventory\Valuation\WeightedAverageInventoryEngine;

class ValuationStrategyResolver
{
    public function resolve(
        ProductVariant $variant
    ): ValuationStrategyInterface
    {
        $product = $variant->product()->first();

        return match ($product->valuation_method) {
            'FIFO' => app(FifoInventoryEngine::class),
            'FEFO' => app(FefoInventoryEngine::class),
            'WAVG' => app(WeightedAverageInventoryEngine::class),
        };
    }
}


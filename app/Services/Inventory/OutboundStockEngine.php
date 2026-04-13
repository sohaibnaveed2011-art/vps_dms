<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProductVariant;
use RuntimeException;

class OutboundStockEngine
{
    public function __construct(
        protected ValuationStrategyResolver $resolver
    ) {}

    public function consume(array $payload): void
    {
        $variant = ProductVariant::query()
            ->lockForUpdate()
            ->find($payload['product_variant_id']);

        if (! $variant) {
            throw new RuntimeException("Variant not found.");
        }

        $strategy = $this->resolver->resolve($variant);

        $strategy->consume($payload, $variant);
    }
}

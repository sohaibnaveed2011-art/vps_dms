<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\ProductVariant;
use App\Services\Inventory\Pricing\PriceResolverService;

class PriceSimulationService
{
    public function simulate($context, array $data)
    {
        $variant = ProductVariant::findOrFail($data['product_variant_id']);

        $price = app(PriceResolverService::class)
            ->resolve($variant, $context);

        return [
            'product_variant_id' => $variant->id,
            'base_price' => $price,
            'quantity' => $data['quantity'],
            'subtotal' => $price * $data['quantity'],
        ];
    }
}

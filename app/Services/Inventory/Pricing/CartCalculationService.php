<?php

namespace App\Services\Inventory\Pricing;

use App\Services\Inventory\Pricing\PromotionEngineService;

class CartCalculationService
{
    public function calculate($context, array $data)
    {
        $items = collect($data['items']);

        $subtotal = $items->sum(fn ($item) =>
            $item['price'] * $item['quantity']
        );

        $discount = app(PromotionEngineService::class)
            ->apply($items, $subtotal);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $subtotal - $discount,
        ];
    }
}

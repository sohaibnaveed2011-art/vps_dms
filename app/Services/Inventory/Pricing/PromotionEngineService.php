<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\Promotion;
use Illuminate\Support\Collection;

class PromotionEngineService
{
    public function apply(Collection $items, float $subtotal, int $organizationId): float
    {
        $discount = 0;

        $promotions = Promotion::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderByDesc('priority')
            ->get();

        foreach ($promotions as $promotion) {

            /*
            |--------------------------------------------------------------------------
            | Min Order Check
            |--------------------------------------------------------------------------
            */

            if ($promotion->min_order_amount &&
                $subtotal < $promotion->min_order_amount) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate Discount
            |--------------------------------------------------------------------------
            */

            $promoDiscount = match ($promotion->type) {
                'percentage' => $subtotal * ($promotion->value / 100),
                'fixed' => $promotion->value,
            };

            $discount += $promoDiscount;

            /*
            |--------------------------------------------------------------------------
            | Stop if not stackable
            |--------------------------------------------------------------------------
            */

            if (!$promotion->stackable) {
                break;
            }
        }

        return $discount;
    }
}

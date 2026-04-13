<?php

namespace App\Services\Inventory\Pricing;

use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\PriceList;
use Illuminate\Support\Facades\Cache;

class PriceResolverService
{
    public function resolve(ProductVariant $variant, $context): float
    {
        $cacheKey = sprintf(
            'price:%s:%s:%s',
            $context->organization_id,
            $context->branch_id ?? 'org',
            $variant->id
        );

        return Cache::tags(['pricing'])->remember($cacheKey, 3600, function () use ($variant, $context) {

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ Branch / Location Override
            |--------------------------------------------------------------------------
            */

            $override = $variant->prices()
                ->where('priceable_type', $context->branch_type ?? null)
                ->where('priceable_id', $context->branch_id ?? null)
                ->first();

            if ($override && $override->sale_price !== null) {
                return (float) $override->sale_price;
            }

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ Active Price Lists (Priority Based)
            |--------------------------------------------------------------------------
            */

            $priceList = PriceList::query()
                ->where('organization_id', $context->organization_id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('starts_at')
                      ->orWhere('starts_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('ends_at')
                      ->orWhere('ends_at', '>=', now());
                })
                ->orderByDesc('priority')
                ->first();

            if ($priceList) {
                $item = $priceList->items()
                    ->where('product_variant_id', $variant->id)
                    ->first();

                if ($item) {
                    return (float) $item->price;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ Fallback
            |--------------------------------------------------------------------------
            */

            return (float) $variant->sale_price;
        });
    }
}

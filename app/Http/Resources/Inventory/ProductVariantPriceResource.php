<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantPriceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'priceable_type' => $this->priceable_type,
            'priceable_id' => $this->priceable_id,
            'cost_price' => $this->cost_price,
            'sale_price' => $this->sale_price,
            'is_override' => $this->is_override,
        ];
    }
}


<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceListItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'price_list_id' => $this->price_list_id,
            'product_variant_id' => $this->product_variant_id,
            'price' => $this->price,
        ];
    }
}


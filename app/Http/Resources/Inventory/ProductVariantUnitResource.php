<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantUnitResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'conversion_factor' => $this->conversion_factor,
            'is_base' => $this->is_base,
            'is_purchase_unit' => $this->is_purchase_unit,
            'is_sale_unit' => $this->is_sale_unit,
        ];
    }
}


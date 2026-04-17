<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantDiscountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'discountable_type' => $this->discountable_type,
            'discountable_id' => $this->discountable_id,
            'type' => $this->type,
            'value' => $this->value,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
        ];
    }
}


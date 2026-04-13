<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryReservationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'stock_location_id' => $this->stock_location_id,
            'product_variant_id' => $this->product_variant_id,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


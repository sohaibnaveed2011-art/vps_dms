<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class SerialNumberResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'inventory_batch_id' => $this->inventory_batch_id,
            'serial_number' => $this->serial_number,
            'status' => $this->status,
        ];
    }
}


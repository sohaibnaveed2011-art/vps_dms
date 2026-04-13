<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'currency' => $this->currency,
            'is_default' => $this->is_default,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'priority' => $this->priority,
            'is_active' => $this->is_active
        ];
    }
}


<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class StockLocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'code' => $this->code,
            'locatable_type' => $this->locatable_type,
            'locatable_id' => $this->locatable_id,
            'is_active' => (bool) $this->is_active,
        ];
    }
}

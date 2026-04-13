<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryConditionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'organization_id'=> $this->organization_id,
            'name'          => $this->name,
            'is_sellable'   => (bool) $this->is_sellable,
            'is_active'     => (bool) $this->is_active,
        ];
    }
}

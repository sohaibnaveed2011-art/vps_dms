<?php

namespace App\Http\Resources\Partner;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnerCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type, // 'customer' | 'supplier'
            'is_active' => (bool) $this->is_active,
        ];
    }
}

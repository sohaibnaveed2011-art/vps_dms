<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandModelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            // 'organization_id' => $this->organization_id,
            // 'brand_id'        => $this->brand_id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'series'          => $this->series,
            'is_active'       => (bool) $this->is_active,
        ];
    }
}

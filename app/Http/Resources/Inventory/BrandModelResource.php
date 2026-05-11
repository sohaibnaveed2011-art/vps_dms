<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Inventory\MiniResources\BrandMiniResource;

class BrandModelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'series'          => $this->series,
            'is_active'       => (bool) $this->is_active,
            'brand'           => new BrandMiniResource($this->whenLoaded('brand')),
        ];
    }
}

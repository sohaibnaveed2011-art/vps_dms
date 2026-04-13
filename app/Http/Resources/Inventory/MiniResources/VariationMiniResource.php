<?php

namespace App\Http\Resources\Inventory\MiniResources;

use Illuminate\Http\Resources\Json\JsonResource;

class VariationMiniResource extends JsonResource
{
    public function toArray($request): array
    {
        if (! $this->resource) return [];

        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

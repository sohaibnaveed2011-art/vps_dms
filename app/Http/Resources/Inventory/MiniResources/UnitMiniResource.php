<?php

namespace App\Http\Resources\Inventory\MiniResources;

use Illuminate\Http\Resources\Json\JsonResource;

class UnitMiniResource extends JsonResource
{
    public function toArray($request): array
    {
        if (! $this->resource) return [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
        ];
    }
}

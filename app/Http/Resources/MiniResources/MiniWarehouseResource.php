<?php

namespace App\Http\Resources\MiniResources;

use Illuminate\Http\Resources\Json\JsonResource;

class MiniWarehouseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

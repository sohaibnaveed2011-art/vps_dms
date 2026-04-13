<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MiniResources\MiniOutletResource;

class OutletSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_pos_counter' => (bool)$this->is_pos_counter,
            'display_order' => (int)$this->display_order,
            'is_active' => (bool)$this->is_active,
            'outlet'     => new MiniOutletResource($this->whenLoaded('outlet')),
        ];
    }
}

<?php

namespace App\Http\Resources\MiniResources;

use App\Http\Resources\Inventory\BrandModelResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MiniBranchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'models'=> BrandModelResource::collection($this->whenLoaded('models')),
        ];
    }
}

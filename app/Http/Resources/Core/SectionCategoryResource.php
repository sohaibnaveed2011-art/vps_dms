<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Resources\Json\JsonResource;

class SectionCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}

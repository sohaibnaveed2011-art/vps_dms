<?php

namespace App\Http\Resources\Core;

use App\Http\Resources\MiniResources\MiniSectionCategoryResource;
use App\Http\Resources\MiniResources\MiniWarehouseResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseSectionResource extends JsonResource
{    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'warehouse'             => new MiniWarehouseResource($this->whenLoaded('warehouse')),
            'parent_section_id'     => $this->parent_section_id,
            'section_category'      => new MiniSectionCategoryResource($this->whenLoaded('sectionCategory')),
            'hierarchy_path'        => $this->hierarchy_path,
            'level'                 => (int) $this->level,
            'name'                  => $this->name,
            'code'                  => $this->code,
            'zone'                  => $this->zone,
            'aisle'                 => $this->aisle,
            'rack'                  => $this->rack,
            'shelf'                 => $this->shelf,
            'bin'                   => $this->bin,
            'description'           => $this->description,
            'is_active'             => (bool) $this->is_active,
        ];
    }
}

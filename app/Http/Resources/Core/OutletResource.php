<?php

namespace App\Http\Resources\Core;

use App\Http\Resources\MiniResources\MiniOrganizationResource;
use App\Http\Resources\MiniResources\MiniBranchResource;
use App\Http\Resources\MiniResources\MiniWarehouseResource;

use Illuminate\Http\Resources\Json\JsonResource;

class OutletResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'organization'  => new MiniOrganizationResource($this->whenLoaded('organization')),
            'branch'        => new MiniBranchResource($this->whenLoaded('branch')),
            'warehouse'     => new MiniWarehouseResource($this->whenLoaded('warehouse')),
            'name'          => $this->name,
            'code'          => $this->code,
            'email'         => $this->email,
            'contact_person'=> $this->contact_person,
            'contact_no'    => $this->contact_no,
            'address'       => $this->address,
            'city'          => $this->city,
            'state'         => $this->state,
            'country'       => $this->country,
            'zip_code'      => $this->zip_code,
            'longitude'     => $this->longitude !== null ? (float) $this->longitude : null,
            'latitude'      => $this->latitude !== null ? (float) $this->latitude : null,
            'is_active'     => (bool) $this->is_active,
        ];
    }
}

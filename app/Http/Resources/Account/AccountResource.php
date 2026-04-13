<?php

namespace App\Http\Resources\Account;

use App\Http\Resources\Core\OrganizationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'organization' => new OrganizationResource($this->whenLoaded('organization')),
        ];
    }
}

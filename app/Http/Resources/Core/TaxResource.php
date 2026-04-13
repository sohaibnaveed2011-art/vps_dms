<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // The rate is cast to decimal:4 in the model, so we send it as a float/string
        // but ensure it maintains 4 decimal places for precision on the client side.
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'rate' => $this->rate,
            'is_active' => (bool) $this->is_active,

            // Relationships
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
        ];
    }
}

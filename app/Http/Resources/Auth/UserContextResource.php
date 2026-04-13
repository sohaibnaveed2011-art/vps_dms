<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserContextResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
            'outlet_id' => $this->outlet_id,
            'cash_register_id' => $this->cash_register_id,

            // ISO timestamps (null-safe)
            'started_at' => optional($this->started_at)?->toIsoString(),
            'ended_at' => optional($this->ended_at)?->toIsoString(),
            // 'created_at' => optional($this->created_at)?->toIsoString(),
            // 'updated_at' => optional($this->updated_at)?->toIsoString(),

            // Minimal related data to avoid leaking sensitive info
            'user' => $this->whenLoaded('user', function () {
                // expose only minimal user information
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            // If you have dedicated Resources for these models you may return them:
            // return new \App\Http\Resources\Core\OrganizationResource($this->organization)
            // Here we return the model (or null) when loaded — clients can use fields they need.
            'organization' => $this->whenLoaded('organization', fn () => $this->organization),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse),
            'outlet' => $this->whenLoaded('outlet', fn () => $this->outlet),
            // relation name is cashRegister, key kept as snake_case for client consistency
            'cash_register' => $this->whenLoaded('cashRegister', fn () => $this->cashRegister),
        ];
    }
}

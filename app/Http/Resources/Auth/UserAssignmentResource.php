<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user'       => new UserResource($this->whenLoaded('user')), // Assuming you have a UserResource
            'role'       => [
                'id'   => $this->role_id,
                'name' => $this->whenLoaded('role', fn() => $this->role->name),
            ],
            'assignable' => $this->whenLoaded('assignable', function () {
                return [
                    'id'      => $this->assignable_id,
                    'type'    => $this->assignable_type, // This is your Morph Map key
                    'display' => $this->assignable->name
                                ?? $this->assignable->legal_name
                                ?? $this->assignable->code
                                ?? 'N/A',
                ];
            }),
            'is_active'  => $this->ended_at === null, // Derive activity from the timestamp
            'started_at' => $this->started_at?->toIsoString(),
            'ended_at'   => $this->ended_at?->toIsoString(),
        ];
    }
}

<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string,mixed>
     */
    public function toArray($request): array
    {
        $user = $this->resource;

        $activeContext = $user->activeContext();

        $activeAssignments = $user->userAssignments()
            ->where('ended_at', null)
            ->with('role')
            ->get();

        return [
            'id'                    => $user->id,
            'name'                  => $user->name,
            'email'                 => $user->email,
            'is_active'             => (bool) $user->is_active,
            'active_context'        => $activeContext ? [
                'id'                => $activeContext->id,
                'organization_id'   => $activeContext->organization_id,
                'branch_id'         => $activeContext->branch_id,
                'warehouse_id'      => $activeContext->warehouse_id,
                'outlet_id'         => $activeContext->outlet_id,
                'cash_register_id'  => $activeContext->cash_register_id,
                'started_at'        => optional($activeContext->started_at)?->toISOString(),
                'ended_at'          => optional($activeContext->ended_at)?->toISOString(),
            ] : null,
            'active_assignments'    => $activeAssignments->map(fn ($a) => [
                'id' => $a->id,
                'assignable_type'   => $a->assignable_type,
                'assignable_id'     => $a->assignable_id,
                'role'              => $a->role?->name,
                'permissions'       => $a->role?->permissions->pluck('name')->unique()->values(),
                'started_at'        => optional($a->started_at)?->toISOString(),
                'ended_at'          => optional($a->ended_at)?->toISOString(),
            ])->values(),
        ];
    }
}

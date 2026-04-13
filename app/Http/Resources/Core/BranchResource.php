<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string,mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'code'             => $this->code,
            'email'            => $this->email,
            'contact_person'   => $this->contact_person,
            'contact_no'       => $this->contact_no,
            'address'          => $this->address,
            'city'             => $this->city,
            'state'            => $this->state,
            'country'          => $this->country,
            'zip_code'         => $this->zip_code,
            'longitude'        => isset($this->longitude) ? (float) $this->longitude : null,
            'latitude'         => isset($this->latitude) ? (float) $this->latitude : null,
            'is_fbr_active'    => (bool) $this->is_fbr_active,
            'pos_id'           => $this->pos_id,
            'pos_auth_token'   => $this->pos_auth_token ? (string) $this->pos_auth_token : null,
            'is_active'        => (bool) $this->is_active,
        ];
    }
}

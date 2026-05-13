<?php

namespace App\Http\Resources\Partner;

use App\Http\Resources\MiniResources\MiniPartnerCategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cnic' => $this->cnic,
            'ntn' => $this->ntn,
            'strn' => $this->strn,
            'incorporation_no' => $this->incorporation_no,
            'contact_person' => $this->contact_person,
            'contact_no' => $this->contact_no,
            'email' => $this->email,
            'address' => $this->address,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'payment_terms_days' => (int) $this->payment_terms_days,
            'current_balance' => (float) $this->current_balance,
            'is_active' => (bool) $this->is_active,
            'partner_category' => new MiniPartnerCategoryResource($this->whenLoaded('category')),
        ];
    }
}

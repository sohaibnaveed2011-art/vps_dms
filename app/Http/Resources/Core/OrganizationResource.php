<?php

namespace App\Http\Resources\Core;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'business_start_date' => $this->business_start_date,
            'ntn' => $this->ntn,
            'strn' => $this->strn,
            'incorporation_no' => $this->incorporation_no,
            'email' => $this->email,
            'contact_no' => $this->contact_no,
            'website' => $this->website,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'zip_code' => $this->zip_code,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'logo' => $this->logo,
            'favicon' => $this->favicon,
            'currency_code' => $this->currency_code,
            'is_active' => $this->is_active,
            // 'financial_years' => $this->whenLoaded('financialYears'),
            // 'branches' => $this->whenLoaded('branches'),
            // 'taxes' => $this->whenLoaded('taxes'),
            // 'user_contexts' => $this->whenLoaded('userContexts'),
        ];
    }
}

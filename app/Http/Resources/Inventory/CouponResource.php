<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'min_order_amount' => $this->min_order_amount,
            'valid_from' => $this->valid_from,
            'valid_to' => $this->valid_to,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


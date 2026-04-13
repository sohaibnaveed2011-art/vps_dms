<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'batch_number' => $this->batch_number,
            'manufacturing_date' => $this->manufacturing_date,
            'expiry_date' => $this->expiry_date,
            'initial_cost' => $this->initial_cost,
            'is_recalled' => $this->is_recalled,
            'recall_reason' => $this->recall_reason,
            'storage_condition' => $this->storage_condition,
            'mrp' => $this->mrp,
            'remaining_quantity' => $this->remaining_quantity,
            'warranty_months' => $this->warranty_months,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


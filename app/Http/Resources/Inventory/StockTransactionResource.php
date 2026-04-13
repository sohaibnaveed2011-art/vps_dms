<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'item_id' => $this->item_id,
            'batch_id' => $this->batch_id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'running_balance' => $this->running_balance,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => $this->whenLoaded('organization'),
            'branch' => $this->whenLoaded('branch'),
            'item' => $this->whenLoaded('item'),
            'batch' => $this->whenLoaded('batch'),
            'reference' => $this->whenLoaded('reference'),
            'created_by_user' => $this->whenLoaded('createdBy'),
        ];
    }
}

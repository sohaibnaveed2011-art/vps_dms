<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'stock_location_id' => $this->stock_location_id,
            'product_variant_id' => $this->product_variant_id,
            'inventory_batch_id' => $this->inventory_batch_id,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'transaction_type' => $this->transaction_type,
            'quantity_in' => $this->quantity_in,
            'quantity_out' => $this->quantity_out,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


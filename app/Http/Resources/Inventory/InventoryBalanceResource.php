<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'stock_location_id' => $this->stock_location_id,
            'product_variant_id' => $this->product_variant_id,
            'inventory_batch_id' => $this->inventory_batch_id,
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'min_stock' => $this->min_stock,
            'reorder_point' => $this->reorder_point,
            'avg_cost' => $this->avg_cost,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


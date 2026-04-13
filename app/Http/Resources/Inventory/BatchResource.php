<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date,
            'manufacture_date' => $this->manufacture_date,
            'quantity' => $this->quantity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'item' => $this->whenLoaded('item'),
            'section_stocks' => $this->whenLoaded('sectionStocks'),
            'stock_transactions' => $this->whenLoaded('stockTransactions'),
        ];
    }
}

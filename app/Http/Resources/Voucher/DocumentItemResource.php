<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'document_type' => $this->document_type,
            'document_id' => $this->document_id,
            'item_id' => $this->item_id,
            'tax_id' => $this->tax_id,
            'batch_id' => $this->batch_id,
            'cost_of_goods_sold' => $this->cost_of_goods_sold,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount_amount' => $this->discount_amount,
            'tax_rate' => $this->tax_rate,
            'line_total' => $this->line_total,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'item' => $this->whenLoaded('item'),
            'tax' => $this->whenLoaded('tax'),
            'batch' => $this->whenLoaded('batch'),
        ];
    }
}

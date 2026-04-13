<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'sale_order_id' => $this->sale_order_id,
            'invoice_id' => $this->invoice_id,
            'document_number' => $this->document_number,
            'date' => $this->date,
            'rider_id' => $this->rider_id,
            'status' => $this->status,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => $this->whenLoaded('organization'),
            'sale_order' => $this->whenLoaded('saleOrder'),
            'invoice' => $this->whenLoaded('invoice'),
            'rider' => $this->whenLoaded('rider'),
            'updated_by_user' => $this->whenLoaded('updatedBy'),
            'items' => DocumentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

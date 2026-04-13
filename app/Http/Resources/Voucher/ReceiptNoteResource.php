<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_bill_id' => $this->purchase_bill_id,
            'document_number' => $this->document_number,
            'date' => $this->date,
            'status' => $this->status,
            'received_by' => $this->received_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => $this->whenLoaded('organization'),
            'purchase_order' => $this->whenLoaded('purchaseOrder'),
            'purchase_bill' => $this->whenLoaded('purchaseBill'),
            'received_by_user' => $this->whenLoaded('receivedBy'),
            'updated_by_user' => $this->whenLoaded('updatedBy'),
            'items' => DocumentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebitNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'purchase_bill_id' => $this->purchase_bill_id,
            'supplier_id' => $this->supplier_id,
            'document_number' => $this->document_number,
            'date' => $this->date,
            'grand_total' => $this->grand_total,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => $this->whenLoaded('organization'),
            'purchase_bill' => $this->whenLoaded('purchaseBill'),
            'supplier' => $this->whenLoaded('supplier'),
            'created_by_user' => $this->whenLoaded('createdBy'),
            'items' => DocumentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

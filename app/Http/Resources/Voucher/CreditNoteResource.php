<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'invoice_id' => $this->invoice_id,
            'customer_id' => $this->customer_id,
            'document_number' => $this->document_number,
            'date' => $this->date,
            'grand_total' => $this->grand_total,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => $this->whenLoaded('organization'),
            'invoice' => $this->whenLoaded('invoice'),
            'customer' => $this->whenLoaded('customer'),
            'created_by_user' => $this->whenLoaded('createdBy'),
            'items' => DocumentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

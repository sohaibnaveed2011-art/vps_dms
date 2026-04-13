<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'supplier_id' => $this->supplier_id,
            'voucher_type_id' => $this->voucher_type_id,
            'document_number' => $this->document_number,
            'supplier_invoice_number' => $this->supplier_invoice_number,
            'date' => $this->date,
            'grand_total' => $this->grand_total,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'reviewed_by' => $this->reviewed_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => $this->whenLoaded('organization'),
            'branch' => $this->whenLoaded('branch'),
            'supplier' => $this->whenLoaded('supplier'),
            'voucher_type' => $this->whenLoaded('voucherType'),
            'created_by_user' => $this->whenLoaded('createdBy'),
            'items' => DocumentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

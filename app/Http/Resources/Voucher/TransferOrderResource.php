<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'document_type' => $this->document_type,
            'document_id' => $this->document_id,
            'source_location_type' => $this->source_location_type,
            'source_location_id' => $this->source_location_id,
            'destination_location_type' => $this->destination_location_type,
            'destination_location_id' => $this->destination_location_id,
            'document_number' => $this->document_number,
            'status' => $this->status,
            'total_quantity' => $this->total_quantity,
            'grand_total_value' => $this->grand_total_value,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => $this->whenLoaded('organization'),
            'requested_by_user' => $this->whenLoaded('requestedBy'),
            'approved_by_user' => $this->whenLoaded('approvedBy'),
            'source_location' => $this->whenLoaded('sourceLocation'),
            'destination_location' => $this->whenLoaded('destinationLocation'),
            'document' => $this->whenLoaded('document'),
            'items' => DocumentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

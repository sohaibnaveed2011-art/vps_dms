<?php

namespace App\Http\Resources\Voucher;

use App\Http\Resources\Partner\CustomerResource;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'date' => $this->date?->toDateString(),
            'grand_total' => $this->grand_total,
            'status' => $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
        ];
    }
}

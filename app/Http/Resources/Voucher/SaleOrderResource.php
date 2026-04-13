<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'document_number' => $this->document_number,
            'status'          => $this->status,

            'organization' => [
                'id'   => $this->organization_id,
                'name' => optional($this->organization)->name,
            ],

            'customer' => [
                'id'   => $this->customer_id,
                'name' => optional($this->customer)->name,
            ],

            'dates' => [
                'order_date'    => $this->order_date?->toDateString(),
                'delivery_date' => $this->delivery_date?->toDateString(),
            ],

            'amounts' => [
                'grand_total' => $this->grand_total,
            ],

            'workflow' => [
                'reviewed_at' => $this->reviewed_at,
                'approved_at' => $this->approved_at,
            ],

            'users' => [
                'created_by'  => optional($this->creator)->name,
                'reviewed_by' => optional($this->reviewer)->name,
                'approved_by' => optional($this->approver)->name,
            ],

            'items' => $this->items->map(fn ($item) => [
                'id'        => $item->id,
                'item_id'   => $item->item_id,
                'item_name' => optional($item->item)->name,
                'quantity'  => $item->quantity,
                'unit_price'=> $item->unit_price,
                'discount'  => $item->discount_amount,
                'tax_rate'  => $item->tax_rate,
                'line_total'=> $item->line_total,
                'notes'     => $item->notes,
            ]),

            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
        ];
    }
}

<?php

namespace App\Http\Resources\Voucher;

use App\Http\Resources\Inventory\ProductVariantResource;
use App\Http\Resources\Accounting\TaxResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product_variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
            'tax' => new TaxResource($this->whenLoaded('tax')),
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount_amount' => (float) $this->discount_amount,
            'tax_rate' => (float) $this->tax_rate,
            'line_total' => (float) $this->line_total,
            'notes' => $this->notes,
            
            // Calculated fields
            'subtotal' => round($this->quantity * $this->unit_price, 4),
            'tax_amount' => round(($this->quantity * $this->unit_price - $this->discount_amount) * ($this->tax_rate / 100), 4),
            'net_total' => round($this->quantity * $this->unit_price - $this->discount_amount, 4),
        ];
    }
}
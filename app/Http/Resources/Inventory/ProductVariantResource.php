<?php

namespace App\Http\Resources\Inventory;

use App\Http\Resources\Inventory\MiniResources\VariationValueMiniResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'product_id'        => $this->product_id,
            'sku'               => $this->sku,
            'barcode'           => $this->barcode,
            'cost_price'        => $this->cost_price,
            'sale_price'        => $this->sale_price,
            'is_active'         => $this->is_active,
            'variant_images'    => ProductImageResource::collection($this->whenLoaded('images') ?? []),
             // Nested Variation (e.g., Color)
            // Nested Variation Values (e.g., Color: Red)
            'variation_values'  => VariationValueMiniResource::collection($this->whenLoaded('variationValues')),
            // Nested Units (e.g., Piece, Box)
            'units'             => ProductVariantUnitResource::collection($this->whenLoaded('units')),
        ];
    }
}

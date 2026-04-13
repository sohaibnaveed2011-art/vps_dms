<?php

namespace App\Http\Resources\Inventory;

use App\Http\Resources\Inventory\MiniResources\BrandMiniResource;
use App\Http\Resources\Inventory\MiniResources\BrandModelMiniResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BookerItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            // Basic Info
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'sale_price' => $this->sale_price,
            "image" => "https://placehold.co/600x400",

            // Category / Brand / Model
            'brand' => new BrandMiniResource($this->whenLoaded('brand')),
            'brand_model' => new BrandModelMiniResource($this->whenLoaded('brandModel')),

            // Nested Variation Values (item_variation_value pivot)
            'variation_values' => VariationValueResource::collection(
                $this->whenLoaded('variationValues')
            ),
        ];
    }
}

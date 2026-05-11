<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use App\Http\Resources\Core\TaxResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Inventory\MiniResources\BrandMiniResource;
use App\Http\Resources\Inventory\MiniResources\CategoryMiniResource;
use App\Http\Resources\Inventory\MiniResources\VariationMiniResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'category'          => new CategoryMiniResource($this->whenLoaded('category')),
            'brand'             => new BrandMiniResource($this->whenLoaded('brand')),
            'brand_model'       => new BrandModelResource($this->whenLoaded('brandModel')),
            'tax'               => new TaxResource($this->whenLoaded('tax')),
            'description'       => $this->description,
            'valuation_method'  => $this->valuation_method,
            'has_warranty'      => $this->has_warranty,
            'warranty_months'   => $this->warranty_months,
            'has_variants'      => $this->has_variants,
            'is_active'         => $this->is_active,
            'product_images'    => ProductImageResource::collection($this->whenLoaded('images') ?? []),
            'variations'        => VariationMiniResource::collection($this->whenLoaded('variations') ?? []),
            'variants'          => ProductVariantResource::collection($this->whenLoaded('variants') ?? []),

        ];
    }
}

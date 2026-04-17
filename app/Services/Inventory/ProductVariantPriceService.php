<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Exceptions\NotFoundException;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\ProductVariantPrice;
use Illuminate\Validation\ValidationException;

class ProductVariantPriceService
{
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false)
    {
        $query = ProductVariantPrice::query();

        if ($withTrashed) $query->withTrashed();
        if ($orgId) $query->where('organization_id', $orgId);

        return $query->find($id) ?? throw new NotFoundException('Product Variant Price not found.');
    }

    public function create(array $data): ProductVariantPrice
    {
        return DB::transaction(function () use ($data) {
            
            $this->validateVariantOwnership($data);

            $this->validateAtLeastOnePrice($data);

            $this->preventDuplicate($data);

            return ProductVariantPrice::create($data);
        });
    }

    public function update(ProductVariantPrice $price, array $data): ProductVariantPrice
    {
        return DB::transaction(function () use ($price, $data) {

            if (isset($data['product_variant_id'])) {
                $this->validateVariantOwnership($data);
            }

            $this->validateAtLeastOnePrice($data);

            $price->update($data);

            return $price;
        });
    }

    public function delete(ProductVariantPrice $price): void
    {
        $price->delete();
    }

    public function restore(ProductVariantPrice $price): void
    {
        $price->restore();
    }

    public function forceDelete(ProductVariantPrice $price): void
    {
        $price->forceDelete();
    }

    /**
     * 🔥 BULK UPSERT (IMPORTANT)
     */
    public function bulkUpsert(array $data): bool
    {
        return DB::transaction(function () use ($data) {

            $records = [];

            foreach ($data['items'] as $item) {

                $this->validateAtLeastOnePrice($item);

                $records[] = [
                    'organization_id' => $data['organization_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'priceable_type' => $data['priceable_type'],
                    'priceable_id' => $data['priceable_id'],
                    'sale_price' => $item['sale_price'] ?? null,
                    'cost_price' => $item['cost_price'] ?? null,
                    'is_override' => $data['is_override'] ?? false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ProductVariantPrice::upsert(
                $records,
                ['product_variant_id', 'priceable_type', 'priceable_id'],
                ['sale_price', 'cost_price', 'is_override', 'updated_at']
            );

            return true;
        });
    }

    private function validateVariantOwnership(array $data): void
    {
        $exists = ProductVariant::where('id', $data['product_variant_id'])
            ->where('organization_id', $data['organization_id'])
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Invalid variant for this organization'
            ]);
        }
    }

    private function validateAtLeastOnePrice(array $data): void
    {
        if (
            (!isset($data['sale_price']) || $data['sale_price'] === null) &&
            (!isset($data['cost_price']) || $data['cost_price'] === null)
        ) {
            throw ValidationException::withMessages([
                'price' => 'Either cost_price or sale_price is required'
            ]);
        }
    }

    private function preventDuplicate(array $data): void
    {
        $exists = ProductVariantPrice::where([
            'organization_id' => $data['organization_id'],
            'product_variant_id' => $data['product_variant_id'],
            'priceable_type' => $data['priceable_type'],
            'priceable_id' => $data['priceable_id'],
        ])->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'duplicate' => 'Price already exists for this variant and scope.'
            ]);
        }
    }
}
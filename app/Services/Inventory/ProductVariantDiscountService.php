<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Exceptions\NotFoundException;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\ProductVariantDiscount;
use Illuminate\Validation\ValidationException;

class ProductVariantDiscountService
{
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false)
    {
        $query = ProductVariantDiscount::query();

        if ($withTrashed) $query->withTrashed();
        if ($orgId) $query->where('organization_id', $orgId);

        return $query->find($id) ?? throw new NotFoundException('Product Variant Discount not found.');
    }

    public function create(array $data): ProductVariantDiscount
    {
        return DB::transaction(function () use ($data) {
            
            $this->validateVariantOwnership($data);

            $this->preventDuplicate($data);

            return ProductVariantDiscount::create($data);
        });
    }

    public function update(ProductVariantDiscount $discount, array $data): ProductVariantDiscount
    {
        return DB::transaction(function () use ($discount, $data) {

            if (isset($data['product_variant_id'])) {
                $this->validateVariantOwnership($data);
            }

            $discount->update($data);

            return $discount;
        });
    }

    public function delete(ProductVariantDiscount $discount): void
    {
        $discount->delete();
    }

    public function restore(ProductVariantDiscount $discount): void
    {
        $discount->restore();
    }

    public function forceDelete(ProductVariantDiscount $discount): void
    {
        $discount->forceDelete();
    }

    /**
     * 🔥 BULK UPSERT (IMPORTANT)
     */
    public function bulkUpsert(array $data): bool
    {
        return DB::transaction(function () use ($data) {

            $records = [];

            foreach ($data['items'] as $item) {

                $records[] = [
                    'organization_id' => $data['organization_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'Discountable_type' => $data['Discountable_type'],
                    'Discountable_id' => $data['Discountable_id'],
                    'sale_Discount' => $item['sale_Discount'] ?? null,
                    'cost_Discount' => $item['cost_Discount'] ?? null,
                    'is_override' => $data['is_override'] ?? false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ProductVariantDiscount::upsert(
                $records,
                ['product_variant_id', 'Discountable_type', 'Discountable_id'],
                ['sale_Discount', 'cost_Discount', 'is_override', 'updated_at']
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

    private function preventDuplicate(array $data): void
    {
        $exists = ProductVariantDiscount::where([
            'organization_id' => $data['organization_id'],
            'product_variant_id' => $data['product_variant_id'],
            'Discountable_type' => $data['Discountable_type'],
            'Discountable_id' => $data['Discountable_id'],
        ])->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'duplicate' => 'Discount already exists for this variant and scope.'
            ]);
        }
    }
}
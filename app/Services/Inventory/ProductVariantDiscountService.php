<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Exceptions\NotFoundException;
use App\Models\Inventory\ProductVariant;
use Illuminate\Validation\ValidationException;
use App\Models\Inventory\ProductVariantDiscount;

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
            
            // Validate required fields
            if (empty($data['value']) || $data['value'] <= 0) {
                throw ValidationException::withMessages([
                    'value' => 'Discount value must be greater than zero.'
                ]);
            }
            
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
            
            // Validate value if being updated
            if (isset($data['value']) && $data['value'] <= 0) {
                throw ValidationException::withMessages([
                    'value' => 'Discount value must be greater than zero.'
                ]);
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
     * 🔥 BULK UPSERT
     */
    public function bulkUpsert(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            
            $records = [];
            
            foreach ($data['items'] as $item) {
                // Validate variant ownership for each item
                $this->validateVariantOwnership([
                    'organization_id' => $data['organization_id'],
                    'product_variant_id' => $item['product_variant_id'],
                ]);
                
                // Validate value is positive
                if (empty($item['value']) || $item['value'] <= 0) {
                    throw ValidationException::withMessages([
                        "value" => "Discount value must be greater than zero for variant ID {$item['product_variant_id']}."
                    ]);
                }
                
                // Validate max_discount_amount is positive if set
                if (!empty($item['max_discount_amount']) && $item['max_discount_amount'] <= 0) {
                    throw ValidationException::withMessages([
                        "max_discount_amount" => "Max discount amount must be greater than zero."
                    ]);
                }
                
                $records[] = [
                    'organization_id' => $data['organization_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'discountable_type' => $data['discountable_type'],
                    'discountable_id' => $data['discountable_id'],
                    'application_type' => $item['application_type'] ?? 'sale',
                    'discount_type' => $item['discount_type'] ?? 'percentage',
                    'value' => $item['value'],
                    'max_discount_amount' => $item['max_discount_amount'] ?? null,
                    'priority' => $item['priority'] ?? 1,
                    'stackable' => $item['stackable'] ?? false,
                    'start_date' => $item['start_date'] ?? null,
                    'end_date' => $item['end_date'] ?? null,
                    'is_active' => $item['is_active'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            ProductVariantDiscount::upsert(
                $records,
                ['organization_id', 'product_variant_id', 'discountable_type', 'discountable_id', 'application_type'],
                ['value', 'max_discount_amount', 'priority', 'stackable', 'start_date', 'end_date', 'is_active', 'updated_at']
            );
            
            return true;
        });
    }

    /**
     * Alternative: Bulk upsert that preserves existing records without changing created_at
     */
    public function bulkUpsertPreserveExisting(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            
            foreach ($data['items'] as $item) {
                // Validate variant ownership
                $this->validateVariantOwnership([
                    'organization_id' => $data['organization_id'],
                    'product_variant_id' => $item['product_variant_id'],
                ]);
                
                // Validate value
                if (empty($item['value']) || $item['value'] <= 0) {
                    throw ValidationException::withMessages([
                        "value" => "Discount value must be greater than zero for variant ID {$item['product_variant_id']}."
                    ]);
                }
                
                // Update or create individually to preserve created_at
                ProductVariantDiscount::updateOrCreate(
                    [
                        'organization_id' => $data['organization_id'],
                        'product_variant_id' => $item['product_variant_id'],
                        'discountable_type' => $data['discountable_type'],
                        'discountable_id' => $data['discountable_id'],
                        'application_type' => $item['application_type'] ?? 'sale',
                    ],
                    [
                        'discount_type' => $item['discount_type'] ?? 'percentage',
                        'value' => $item['value'],
                        'max_discount_amount' => $item['max_discount_amount'] ?? null,
                        'priority' => $item['priority'] ?? 1,
                        'stackable' => $item['stackable'] ?? false,
                        'start_date' => $item['start_date'] ?? null,
                        'end_date' => $item['end_date'] ?? null,
                        'is_active' => $item['is_active'] ?? true,
                        'updated_at' => now(),
                    ]
                );
            }
            
            return true;
        });
    }

    /**
     * Validate that the variant belongs to the organization
     */
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

    /**
     * Prevent duplicate discount for same scope and application type
     */
    private function preventDuplicate(array $data): void
    {
        // 🔥 FIXED: Correct column names (lowercase)
        $exists = ProductVariantDiscount::where([
            'organization_id' => $data['organization_id'],
            'product_variant_id' => $data['product_variant_id'],
            'discountable_type' => $data['discountable_type'],    // Fixed: was 'Discountable_type'
            'discountable_id' => $data['discountable_id'],        // Fixed: was 'Discountable_id'
            'application_type' => $data['application_type'] ?? 'sale', // Also include application_type
        ])->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'duplicate' => 'Discount already exists for this variant, scope, and application type.'
            ]);
        }
    }
    
    /**
     * Get all active discounts for a variant
     */
    public function getActiveDiscountsForVariant(int $variantId, ?int $orgId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = ProductVariantDiscount::where('product_variant_id', $variantId)
            ->active()
            ->orderBy('priority', 'desc');
            
        if ($orgId) {
            $query->where('organization_id', $orgId);
        }
        
        return $query->get();
    }
    
    /**
     * Get best (highest priority) discount for a variant and scope
     */
    public function getBestDiscount(int $variantId, string $scopeType, int $scopeId, string $applicationType = 'sale'): ?ProductVariantDiscount
    {
        return ProductVariantDiscount::where('product_variant_id', $variantId)
            ->where('discountable_type', $scopeType)
            ->where('discountable_id', $scopeId)
            ->where('application_type', $applicationType)
            ->active()
            ->orderedByPriority()
            ->first();
    }
}
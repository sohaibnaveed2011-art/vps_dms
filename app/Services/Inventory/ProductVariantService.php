<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Product;
use Illuminate\Support\Facades\DB;
use App\Exceptions\NotFoundException;
use App\Models\Inventory\ProductVariant;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductVariantService
{
    protected array $relations = [
        'product',
        'units',
        'variationValues',
        'images',
        'discounts',
        'prices',
    ];

    /**
     * Paginate variants with product and organization filtering
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ProductVariant::query()
            ->with($this->relations)
            ->when(isset($filters['product_id']), fn($q) => $q->where('product_id', $filters['product_id']))
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['sku']), fn($q) => $q->where('sku', 'like', "%{$filters['sku']}%"))
            ->when(!empty($filters['barcode']), fn($q) => $q->where('barcode', 'like', "%{$filters['barcode']}%"))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%{$filters['search']}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find variant with ownership validation
     */
    public function find(int $id, ?int $orgId = null, ?int $productId = null, bool $withTrashed = false): ProductVariant
    {
        $query = ProductVariant::query()->with($this->relations);

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        if ($productId !== null) {
            $query->where('product_id', $productId);
        }

        $variant = $query->find($id);

        if (!$variant) {
            throw new NotFoundException('Product variant not found.');
        }

        return $variant;
    }

    /**
     * Create new variant
     */
    public function create(array $data): ProductVariant
    {
        return DB::transaction(function () use ($data) {
            // Validate product ownership
            $this->validateProductOwnership($data);

            // Validate unique SKU
            $this->validateUniqueSku($data);

            // Create variant
            $variant = ProductVariant::create($data);

            // Sync units
            if (!empty($data['units'])) {
                $variant->units()->createMany($data['units']);
            }

            // Sync variation values
            if (!empty($data['variation_value_ids'])) {
                $variant->variationValues()->sync($data['variation_value_ids']);
            }

            // Handle images
            if (!empty($data['images'])) {
                app(ProductImageService::class)->syncImages($variant, $data['images']);
            }

            return $variant->load($this->relations);
        });
    }

    /**
     * Update variant
     */
    public function update(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data) {
            // Validate unique SKU (excluding current)
            if (isset($data['sku']) && $data['sku'] !== $variant->sku) {
                $this->validateUniqueSku($data, $variant->id);
            }

            $variant->update($data);

            // Sync units (replace all)
            if (isset($data['units'])) {
                $variant->units()->delete();
                $variant->units()->createMany($data['units']);
            }

            // Sync variation values
            if (isset($data['variation_value_ids'])) {
                $variant->variationValues()->sync($data['variation_value_ids']);
            }

            // Sync images
            if (isset($data['images'])) {
                app(ProductImageService::class)->syncImages($variant, $data['images']);
            }

            return $variant->load($this->relations);
        });
    }

    /**
     * Soft delete variant
     */
    public function delete(ProductVariant $variant): void
    {
        // Check if variant has stock
        if ($variant->inventoryBalances()->sum('quantity') > 0) {
            throw ValidationException::withMessages([
                'variant' => 'Cannot delete variant with existing stock.'
            ]);
        }

        $variant->delete();
    }

    /**
     * Restore soft-deleted variant
     */
    public function restore(ProductVariant $variant): void
    {
        if (!$variant->trashed()) {
            throw new NotFoundException('Product variant is not deleted.');
        }

        $variant->restore();
    }

    /**
     * Permanently delete variant
     */
    public function forceDelete(ProductVariant $variant): void
    {
        // Check for ledger entries
        if ($variant->inventoryLedger()->exists()) {
            throw ValidationException::withMessages([
                'variant' => 'Cannot permanently delete variant with ledger history.'
            ]);
        }

        $variant->forceDelete();
    }

    /**
     * Validate product ownership
     */
    protected function validateProductOwnership(array $data): void
    {
        $exists = Product::where('id', $data['product_id'])
            ->where('organization_id', $data['organization_id'])
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'product_id' => 'Invalid product for this organization.'
            ]);
        }
    }

    /**
     * Validate unique SKU
     */
    protected function validateUniqueSku(array $data, ?int $excludeId = null): void
    {
        $query = ProductVariant::where('sku', $data['sku'])
            ->where('organization_id', $data['organization_id']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'sku' => 'SKU must be unique within the organization.'
            ]);
        }
    }
}
<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    protected array $relations = [
        'category', 
        'brand', 
        'tax', 
        'variations', 
        'variants', // Load the variants
        'variants.units', // Load units for each variant
        'variants.variationValues' // Load values for each variant
    ];

    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        return Product::query()
            ->with($this->relations)
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
            ->when(isset($filters['brand_id']), fn($q) => $q->where('brand_id', $filters['brand_id']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%" . trim($filters['search']) . "%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id, ?int $orgId = null, bool $withTrashed = false)
    {
        $query = Product::query();
        $query->with($this->relations);

        if ($withTrashed) $query->withTrashed();
        if ($orgId != null) $query->where('organization_id', $orgId);

        return $query->find($id) ?? throw new NotFoundException('Product not found.');
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the Product
            $product = Product::create($data);

            // 2. Sync Product Attributes (The "Missing" Variation link)
            if (!empty($data['variation_ids'])) {
                $product->variations()->sync($data['variation_ids']);
            }

            // 3. Handle Variants
            // If has_variants is false, we ensure the first element of 'variants' 
            // is treated as the master record.
            foreach ($data['variants'] as $variantData) {
                $this->saveVariant($product, $variantData);
            }

            return $product->load($this->relations);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($data);

            if (isset($data['variation_ids'])) {
                $existingIds = $product->variations()->pluck('variation_id')->toArray();
                $incomingIds = $data['variation_ids'];

                // 1. "Delete" (SoftDelete) variations that are not in the new list
                $product->variations()
                    ->whereNotIn('variation_id', $incomingIds)
                    ->each(function ($pivot) {
                        $pivot->pivot->delete(); // This triggers SoftDelete if using custom pivot
                    });

                // 2. "Restore" or Add variations
                foreach ($incomingIds as $id) {
                    // use toggle or manual update to handle restoration of soft-deleted rows
                    $product->variations()->syncWithoutDetaching([$id]);
                }
            }

            if (isset($data['variants'])) {
                $incomingIds = collect($data['variants'])->pluck('id')->filter()->toArray();

                // Delete variants not in the update payload
                $product->variants()->whereNotIn('id', $incomingIds)->delete();

                foreach ($data['variants'] as $variantData) {
                    $this->saveVariant($product, $variantData);
                }
            }

            return $product->load($this->relations);
        });
    }

    protected function saveVariant(Product $product, array $variantData): ProductVariant
    {
        $variant = $product->variants()->updateOrCreate(
            ['id' => $variantData['id'] ?? null],
            [
                'sku'               => $variantData['sku'],
                'barcode'           => $variantData['barcode'] ?? null,
                'cost_price'        => $variantData['cost_price'],
                'sale_price'        => $variantData['sale_price'],
                'is_serial_tracked' => $variantData['is_serial_tracked'] ?? false,
                'is_active'         => $variantData['is_active'] ?? true,
            ]
        );

        // Sync Units: Using delete/create is okay for now, but 
        // updateOrCreate is safer for long-term inventory history.
        $variant->units()->delete();
        if (!empty($variantData['units'])) {
            $variant->units()->createMany($variantData['units']);
        }

        // Sync Variation Values (e.g., Small, Red)
        $variant->variationValues()->sync($variantData['variation_value_ids'] ?? []);

        return $variant;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function restore(Product $product): void
    {
        $product->restore();
    }

    public function forceDelete(Product $product): void
    {
        if ($product->variants()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Cannot permanently delete product with existing variants.'
            ]);
        }
        $product->forceDelete();
    }
}

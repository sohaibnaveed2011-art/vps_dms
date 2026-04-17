<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Services\Inventory\ProductImageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;

class ProductService
{
    protected array $relations = [
        'category', 
        'brand', 
        'tax', 
        'images', // Load product images
        'variations', // load product variations (e.g., Size, Color)
        'variants', // Load the variants
        'variants.units', // Load units for each variant
        'variants.variationValues', // Load values for each variant
        'variants.images' // Load product variant images
    ];

    public function __construct(protected ProductImageService $imageService)
    {}

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

            // 3. Sync Product Images
            if (!empty($data['images'])) {
                $this->handleImageUploads($product, $data['images']);
            }

            // 4. Handle Variants
            // If has_variants is false, we ensure the first element of 'variants' 
            // is treated as the master record.
            foreach ($data['variants'] as $variantData) {
                $this->saveVariant($product, $variantData, $data['organization_id']);
            }

            return $product->load($this->relations);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($data);

            // 1. Variations Sync (with SoftDelete logic as per your snippet)
            if (isset($data['variation_ids'])) {
                $product->variations()->sync($data['variation_ids']);
            }

            // 2. Product Images (Sync logic: Add new, Soft-delete missing)
            if (isset($data['images'])) {
                $this->syncImages($product, $data['images']);
            }

            // 3. Handle Variants
            if (isset($data['variants'])) {
                $incomingIds = collect($data['variants'])->pluck('id')->filter()->toArray();
                $product->variants()->whereNotIn('id', $incomingIds)->delete();

                foreach ($data['variants'] as $variantData) {
                    $this->saveVariant($product, $variantData, $product->organization_id);
                }
            }

            return $product->load($this->relations);
        });
    }

    protected function saveVariant(Product $product, array $variantData, $organizationId): ProductVariant
    {
        $variant = $product->variants()->updateOrCreate(
            ['id' => $variantData['id'] ?? null],
            [
                'organization_id'   => $organizationId,
                'sku'               => $variantData['sku'],
                'barcode'           => $variantData['barcode'] ?? null,
                'cost_price'        => $variantData['cost_price'],
                'sale_price'        => $variantData['sale_price'],
                'is_serial_tracked' => $variantData['is_serial_tracked'] ?? false,
                'is_active'         => $variantData['is_active'] ?? true,
            ]
        );

                // Sync Variant Images
        if (isset($variantData['images'])) {
            $this->syncImages($variant, $variantData['images']);
        }

        // Units and Variation Values
        $variant->units()->delete();
        if (!empty($variantData['units'])) {
            $variant->units()->createMany($variantData['units']);
        }
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

    /**
     * Specialized logic to sync images:-
     * 1. New files get uploaded.
     * 2. Existing IDs stay as is.
     * 3. Missing IDs get soft-deleted.
     **/
    protected function syncImages(Model $model, array $images)
    {
        $keepIds = [];
        $newFiles = [];

        foreach ($images as $image) {
            if (is_numeric($image)) { // It's an existing Image ID
                $keepIds[] = $image;
            } elseif ($image instanceof \Illuminate\Http\UploadedFile) {
                $newFiles[] = $image;
            }
        }

        // Soft delete images not present in the payload
        $model->images()->whereNotIn('id', $keepIds)->delete();

        // Upload new images
        foreach ($newFiles as $file) {
            $this->imageService->upload($model, $file);
        }
    }

    protected function handleImageUploads(Model $model, array $images)
    {
        foreach ($images as $image) {
            if ($image instanceof \Illuminate\Http\UploadedFile) {
                $this->imageService->upload($model, $image);
            }
        }
    }
}

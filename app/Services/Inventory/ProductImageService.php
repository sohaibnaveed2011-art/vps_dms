<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\ProductImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    public function find(int $id, $orgId = null, bool $withTrashed = false)
    {
        $query = ProductImage::query();

        
        if ($withTrashed) $query->withTrashed();
        if ($orgId) $query->where('organization_id', $orgId);

        return $query->find($id) ?? throw new NotFoundException('Record not found.');
    }

    /* =========================================================
     | UPLOAD (polymorphic + bug fix)
     ========================================================= */

    /**
     * Handle initial uploads for a new entity.
     */
    public function handleImageUploads(Model $model, array $images): void
    {
        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $this->upload($model, $image);
            }
        }
    }

    /**
     * Sync images for an existing entity (Add new, keep existing, soft-delete removed)
     * 
     * @param Model $model The product or variant model
     * @param array<int, int|UploadedFile|string> $images Array of image IDs or uploaded files
     */
    public function syncImages(Model $model, $images): void
    {
        // If key is missing from payload, do nothing to preserve current state
        if (is_null($images)) {
            return;
        }

        $images = is_array($images) ? $images : [$images];
        
        $keepIds = [];
        $newFiles = [];

        foreach ($images as $image) {
            // If it's a numeric ID, we want to keep it
            if (is_numeric($image) || (is_string($image) && !($image instanceof UploadedFile) && strlen($image) < 15)) {
                $keepIds[] = $image;
            } 
            // If it's a new file, we upload it
            elseif ($image instanceof UploadedFile) {
                $newFiles[] = $image;
            }
        }

        // 1. Soft Delete any existing image NOT in the 'keep' list
        $model->images()->whereNotIn('id', $keepIds)->delete();

        // 2. Upload brand new files
        foreach ($newFiles as $file) {
            $this->upload($model, $file);
        }
    }
    
    /**
     * Upload a single image for a Product or ProductVariant.
     *
     * @param Model $imageable
     * @param UploadedFile $file
     * @return ProductImage
     * @throws \Exception
     */
    public function upload(Model $imageable, UploadedFile $file): ProductImage
    {
        return DB::transaction(function () use ($imageable, $file) {
            
            // 1. Determine directory based on Model type
            $modelType = class_basename($imageable);
            $directory = match ($modelType) {
                'Product'        => "products/{$imageable->id}",
                'ProductVariant' => "variants/{$imageable->id}",
                default          => throw new \Exception("Unsupported imageable type: {$modelType}"),
            };

            // 2. Handle File Storage
            $path = $file->store($directory, 'public');

            if (!$path) {
                throw new \Exception("Failed to store file on disk.");
            }

            // 3. Determine Image Metadata
            $isPrimary = !$imageable->images()->exists();
            $nextSortOrder = ($imageable->images()->max('sort_order') ?? 0) + 1;

            // 4. Create Database Record (Matching ProductImage $fillable)
            return $imageable->images()->create([
                'path'       => $path,
                'disk'       => 'public',
                'is_primary' => $isPrimary,
                'sort_order' => $nextSortOrder,
            ]);
        });
    }

    /**
     * Optional: Handle multiple uploads
     */
    public function uploadMultiple(Model $imageable, array $files): array
    {
        $uploadedImages = [];
        foreach ($files as $file) {
            $uploadedImages[] = $this->upload($imageable, $file);
        }
        return $uploadedImages;
    }

    public function setPrimary(ProductImage $image)
    {
        return DB::transaction(function () use ($image) {
            // Step 1: Set all sibling images to NOT primary
            $image->imageable->images()->update(['is_primary' => false]);

            // Step 2: Set the chosen one to primary
            $image->update(['is_primary' => true]);

            return $image;
        });
    }

    /* =========================================================
    | REORDER (polymorphic)
    ========================================================= */
    public function reorder(Model $imageable, array $orderedIds)
    {
        dd($imageable, $orderedIds);
        return DB::transaction(function () use ($imageable, $orderedIds) {
            // Start a manual integer counter
            $sortOrder = 1;

            foreach ($orderedIds as $id) {
                // Force $id to integer and increment $sortOrder manually
                $imageable->images()
                    ->where('id', (int)$id)
                    ->update(['sort_order' => $sortOrder]);

                $sortOrder++; // This is safe integer incrementing
            }
        });
    }

    /* =========================================================
     | DELETE (Soft)
     ========================================================= */

    public function delete(ProductImage $image): void
    {
        $image->delete();
    }

    /* =========================================================
     | RESTORE
     ========================================================= */

    public function restore(ProductImage $image): void
    {
        $image->restore();
    }

    /* =========================================================
     | FORCE DELETE
     ========================================================= */

    public function forceDelete(ProductImage $image): void
    {
        Storage::disk($image->disk)->delete($image->path);

        $image->forceDelete();
    }
}
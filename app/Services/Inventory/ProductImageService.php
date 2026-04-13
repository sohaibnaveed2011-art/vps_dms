<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    /* =========================================================
     | LIST
     ========================================================= */

    public function list(Product $product)
    {
        return $product->images()
            ->orderBy('sort_order')
            ->get();
    }

    /* =========================================================
     | UPLOAD
     ========================================================= */

    public function upload(Product $product, UploadedFile $file)
    {
        return DB::transaction(function () use ($product, $file) {

            $path = $file->store(
                "products/{$product->organization_id}/{$product->id}",
                'public'
            );

            $isPrimary = !$product->images()->exists();

            $image = $product->images()->create([
                'path' => $path,
                'disk' => 'public',
                'is_primary' => $isPrimary,
                'sort_order' => $product->images()->max('sort_order') + 1,
            ]);

            return $image;
        });
    }

    /* =========================================================
     | SET PRIMARY
     ========================================================= */

    public function setPrimary(ProductImage $image)
    {
        return DB::transaction(function () use ($image) {

            $image->imageable->images()
                ->update(['is_primary' => false]);

            $image->update(['is_primary' => true]);

            return $image;
        });
    }

    /* =========================================================
     | REORDER
     ========================================================= */

    public function reorder(Product $product, array $orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            $product->images()
                ->where('id', $id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    /* =========================================================
     | DELETE (Soft)
     ========================================================= */

    public function delete(ProductImage $image)
    {
        $image->delete();
    }

    /* =========================================================
     | RESTORE
     ========================================================= */

    public function restore(ProductImage $image)
    {
        $image->restore();
    }

    /* =========================================================
     | FORCE DELETE
     ========================================================= */

    public function forceDelete(ProductImage $image)
    {
        Storage::disk($image->disk)->delete($image->path);

        $image->forceDelete();
    }
}

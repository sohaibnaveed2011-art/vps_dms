<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Resources\Inventory\ProductImageResource;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductImage;
use App\Services\Inventory\ProductImageService;
use Illuminate\Http\Request;

class ProductImageController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.product.view',
        'store'       => 'inventory.product.update',
        'setPrimary'  => 'inventory.product.update',
        'destroy'     => 'inventory.product.update',
        'restore'     => 'inventory.product.update',
        'forceDelete' => 'inventory.product.forceDelete',
    ];

    public function __construct(
        protected ProductImageService $service
    ) {
        parent::__construct();
    }

    /* =========================================================
     | UPLOAD
     ========================================================= */

    public function store(Request $request, int $productId)
    {
        $this->authorizeAction($request);

        $request->validate([
            'image' => ['required', 'image', 'max:5120']
        ]);

        $context = $this->context($request);

        $product = Product::where('organization_id', $context->organization_id)
            ->findOrFail($productId);

        $image = $this->service->upload(
            $product,
            $request->file('image')
        );

        return $this->created(new ProductImageResource($image));
    }

    /* =========================================================
     | SET PRIMARY
     ========================================================= */

    public function setPrimary(Request $request, int $imageId)
    {
        $this->authorizeAction($request);

        $image = ProductImage::findOrFail($imageId);

        $updated = $this->service->setPrimary($image);

        return $this->success(
            new ProductImageResource($updated)
        );
    }

    /* =========================================================
     | DELETE
     ========================================================= */

    public function destroy(Request $request, int $imageId)
    {
        $this->authorizeAction($request);

        $image = ProductImage::findOrFail($imageId);

        $this->service->delete($image);

        return $this->deleted('Image deleted successfully.');
    }

    /* =========================================================
     | RESTORE
     ========================================================= */

    public function restore(Request $request, int $imageId)
    {
        $this->authorizeAction($request);

        $image = ProductImage::withTrashed()
            ->findOrFail($imageId);

        $this->service->restore($image);

        return $this->success([
            'message' => 'Image restored successfully.'
        ]);
    }

    /* =========================================================
     | FORCE DELETE
     ========================================================= */

    public function forceDelete(Request $request, int $imageId)
    {
        $this->authorizeAction($request);

        $image = ProductImage::withTrashed()
            ->findOrFail($imageId);

        $this->service->forceDelete($image);

        return $this->deleted('Image permanently deleted.');
    }
}

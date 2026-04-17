<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreProductImageRequest;
use App\Http\Requests\Inventory\UpdateProductImageRequest;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Services\Inventory\ProductImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImageController extends BaseApiController
{
    protected array $permissions = [
        'store'      => 'inventory.product.create',
        'setPrimary' => 'inventory.product.update',
        'reorder' => 'inventory.product.update',
        'destroy'     => 'inventory.product.destroy',
        'restore'     => 'inventory.product.restore',
        'forceDelete' => 'inventory.product.forceDelete',
    ];

    public function __construct(
        protected ProductImageService $service
    ) {
        parent::__construct();
    }

    /**
     * Store images for either a Product or a Variant
     */
    public function store(StoreProductImageRequest $request, $type, $id)
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');
        $model = ($type === 'products') 
            ? Product::findOrFail($id) 
            : ProductVariant::findOrFail($id);

        $this->service->uploadMultiple($model, $request->file('images'));

        return $this->created('Images uploaded successfully.');
    }

    public function setPrimary(Request $request, int $imageId)
    {
        $this->authorizeAction($request);

        $image = $this->service->find($imageId);

        $updated = $this->service->setPrimary($image);

        return $this->success('Record updated successfully...');
    }

    // public function reorder(UpdateProductImageRequest $request, int $imageId)
    // {
    //     $this->authorizeAction($request);

    //     $image = $this->service->find($imageId);
        
    //     // Explicitly grab only the array of IDs
    //     $orderedIds = $request->validated()->toArray()['ordered_ids'] ?? [];
    //     // Pass the parent (imageable) and the flat array
    //     $this->service->reorder($image->imageable, $orderedIds);

    //     return $this->success('Record updated successfully...');
    // }

    /* =========================================================
     | DELETE
     ========================================================= */

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $image = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $image);
        $this->service->delete($image);

        return $this->deleted('Image deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $image = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $image);
        $this->service->restore($image);

        return $this->success(['message' => 'Image restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $image = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $image);
        $this->service->forceDelete($image);

        return $this->deleted('Image permanently deleted.');
    }
}
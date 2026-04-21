<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\BulkProductVariantPriceRequest;
use App\Http\Requests\Inventory\StoreProductVariantPriceRequest;
use App\Http\Requests\Inventory\UpdateProductVariantPriceRequest;
use App\Http\Resources\Inventory\ProductVariantPriceResource;
use App\Services\Inventory\ProductVariantPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantPriceController extends BaseApiController
{
    protected array $permissions = [
        'store'       => 'inventory.product.create',
        'show'        => 'inventory.product.show',
        'update'      => 'inventory.product.update',
        'destroy'     => 'inventory.product.destroy',
        'restore'     => 'inventory.product.restore',
        'forceDelete' => 'inventory.product.forceDelete',
        'bulk'        => 'inventory.product.create',
    ];

    public function __construct(protected ProductVariantPriceService $service)
    {
        parent::__construct();
    }

    public function store(StoreProductVariantPriceRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        $product_variant_price = $this->service->create($this->getValidatedData($request));

        return $this->created('Product variant price created successfully...');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $product_variant_price = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $product_variant_price);

        return $this->success(new ProductVariantPriceResource($product_variant_price));
    }

    public function update(UpdateProductVariantPriceRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $product_variant_price = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $product_variant_price);
        $updated = $this->service->update($product_variant_price, $request->validated());

        return $this->success('Product variant price updated successfully...');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $product_variant_price = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $product_variant_price);
        $this->service->delete($product_variant_price);

        return $this->deleted('Product variant price deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $product_variant_price = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $product_variant_price);
        $this->service->restore($product_variant_price);

        return $this->success(['message' => 'Product variant price restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $product_variant_price = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $product_variant_price);
        $this->service->forceDelete($product_variant_price);

        return $this->deleted('Product variant price permanently deleted.');
    }

    /**
     * 🔥 BULK API
     */
    public function bulk(BulkProductVariantPriceRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        $this->service->bulkUpsert($this->getValidatedData($request));

        return $this->success('Bulk pricing applied successfully.');
    }
}

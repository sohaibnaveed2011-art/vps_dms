<?php

namespace App\Http\Controllers\v1\api\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Inventory\ProductVariantService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Resources\Inventory\ProductVariantResource;
use App\Http\Requests\Inventory\StoreProductVariantRequest;
use App\Http\Requests\Inventory\UpdateProductVariantRequest;

class ProductVariantController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.product.view',
        'store'       => 'inventory.product.create',
        'show'        => 'inventory.product.show',
        'update'      => 'inventory.product.update',
        'destroy'     => 'inventory.product.destroy',
        'restore'     => 'inventory.product.restore',
        'forceDelete' => 'inventory.product.forceDelete',
    ];

    public function __construct(protected ProductVariantService $service)
    {
        parent::__construct();
    }

    public function index(Request $request, int $productId): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active', 'sku', 'barcode']);
        $filters['product_id'] = $productId;
        $this->restrictToContext($request, $filters);

        $variants = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            ProductVariantResource::collection($variants),
            $this->paginationMetadata($variants)
        );
    }

    public function store(StoreProductVariantRequest $request, int $productId): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        $validated = $this->getValidatedData($request);
        $validated['product_id'] = $productId;

        $variant = $this->service->create($validated);

        return $this->created('Product variant created successfully.');
    }

    public function show(Request $request, int $productId, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $variant = $this->service->find($id, $orgId, $productId);

        $this->authorizeAction($request, $variant);

        return $this->success(new ProductVariantResource($variant));
    }

    public function update(UpdateProductVariantRequest $request, int $productId, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $variant = $this->service->find($id, $orgId, $productId);

        $this->authorizeAction($request, $variant);
        $this->service->update($variant, $request->validated());

        return $this->updated('Product variant updated successfully.');
    }

    public function destroy(Request $request, int $productId, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $variant = $this->service->find($id, $orgId, $productId);

        $this->authorizeAction($request, $variant);
        $this->service->delete($variant);

        return $this->deleted('Product variant deleted successfully.');
    }

    public function restore(Request $request, int $productId, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $variant = $this->service->find($id, $orgId, $productId, withTrashed: true);
        $this->authorizeAction($request, $variant);

        $this->service->restore($variant);

        return $this->success(['message' => 'Product variant restored successfully.']);
    }

    public function forceDelete(Request $request, int $productId, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $variant = $this->service->find($id, $orgId, $productId, withTrashed: true);
        $this->authorizeAction($request, $variant);

        $this->service->forceDelete($variant);

        return $this->deleted('Product variant permanently deleted.');
    }
}
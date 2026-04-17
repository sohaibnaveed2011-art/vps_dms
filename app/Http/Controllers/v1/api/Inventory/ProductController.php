<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Http\Resources\Inventory\ProductResource;
use App\Services\Inventory\ProductImageService;
use App\Services\Inventory\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseApiController
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

    public function __construct(protected ProductService $service, protected ProductImageService $imageService)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'category_id', 'brand_id', 'is_active']);
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            ProductResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        $product = $this->service->create($this->getValidatedData($request));

        return $this->created('Product created successfully...');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $product = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $product);

        return $this->success(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $product = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $product);
        $updated = $this->service->update($product, $request->validated());

        return $this->success('Product updated successfully...');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $product = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $product);
    $this->service->delete($product);

        return $this->deleted('Product deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $product = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $product);
        $this->service->restore($product);

        return $this->success(['message' => 'Product restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $product = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $product);
        $this->service->forceDelete($product);

        return $this->deleted('Product permanently deleted.');
    }
}

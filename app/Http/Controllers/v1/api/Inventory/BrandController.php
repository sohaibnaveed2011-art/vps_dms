<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreBrandRequest;
use App\Http\Requests\Inventory\UpdateBrandRequest;
use App\Http\Resources\Inventory\BrandResource;
use App\Services\Inventory\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.brand.view',
        'store'       => 'inventory.brand.create',
        'show'        => 'inventory.brand.show',
        'update'      => 'inventory.brand.update',
        'destroy'     => 'inventory.brand.destroy',
        'restore'     => 'inventory.brand.restore',
        'forceDelete' => 'inventory.brand.forceDelete',
    ];

    public function __construct(protected BrandService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active']);

        // Mandatory Context Restriction: Forces organization_id into filters
        $this->restrictToContext($request, $filters);

        $brands = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            BrandResource::collection($brands),
            $this->paginationMetadata($brands)
        );
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $this->authorizeAction($request);

        // Enforce feature policy for the active context
        $this->enforcePolicy($request, feature: 'inventory');

        // Automatically injects organization_id from context
        $brand = $this->service->create($this->getValidatedData($request));

        return $this->created('Brand created successfully...!');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        // Scoped lookup using current context org_id
        $brand = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $brand);

        return $this->success(new BrandResource($brand));
    }

    public function update(UpdateBrandRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $brand = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $brand);
        $this->service->update($brand, $request->validated());

        return $this->updated('Brand updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $brand = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $brand);
        $this->service->delete($brand);

        return $this->deleted('Brand deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $brand = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $brand);

        $this->service->restore($brand);

        return $this->success(['message' => 'Brand restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $brand = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $brand);

        $this->service->forceDelete($brand);

        return $this->deleted('Brand permanently deleted.');
    }
}

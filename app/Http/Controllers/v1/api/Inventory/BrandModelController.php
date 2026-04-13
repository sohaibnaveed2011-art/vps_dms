<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreBrandModelRequest;
use App\Http\Requests\Inventory\UpdateBrandModelRequest;
use App\Http\Resources\Inventory\BrandModelResource;
use App\Services\Inventory\BrandModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandModelController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.brandModel.view',
        'store'       => 'inventory.brandModel.create',
        'show'        => 'inventory.brandModel.show',
        'update'      => 'inventory.brandModel.update',
        'destroy'     => 'inventory.brandModel.destroy',
        'restore'     => 'inventory.brandModel.restore',
        'forceDelete' => 'inventory.brandModel.forceDelete',
    ];

    public function __construct(protected BrandModelService $service)
    {
        parent::__construct();
    }

    public function index(Request $request, int $brandId): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active']);
        $filters['brand_id'] = $brandId;

        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            BrandModelResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    public function store(StoreBrandModelRequest $request, int $brandId): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'inventory');

        $validated = $this->getValidatedData($request);
        
        $this->service->create(
            $validated['models'], 
            $brandId, 
            $validated['organization_id']
        );

        return $this->created('Brand Models created successfully.');
    }

    /**
     * Display a specific model.
     */
    public function show(Request $request, int $brandId, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $model = $this->service->find($id, $orgId, $brandId);

        $this->authorizeAction($request, $model);

        return $this->success(new BrandModelResource($model));
    }

    /**
     * Update a specific model.
     */
    public function update(UpdateBrandModelRequest $request, int $brandId, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $model = $this->service->find($id, $orgId, $brandId);

        $this->authorizeAction($request, $model);
        
        // We only allow specific fields to be updated to prevent brand_id hijacking
        $this->service->update($model, $request->only(['name', 'slug', 'series', 'is_active']));

        return $this->updated('Brand Model updated successfully.');
    }

    /**
     * Soft delete a model.
     */
    public function destroy(Request $request, int $brandId, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $model = $this->service->find($id, $orgId, $brandId);

        $this->authorizeAction($request, $model);
        $this->service->delete($model);

        return $this->deleted('Brand Model deleted successfully.');
    }

    /**
     * Restore a soft-deleted model.
     */
    public function restore(Request $request, int $brandId, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $model = $this->service->find($id, $orgId, $brandId, withTrashed: true);
        $this->authorizeAction($request, $model);

        $this->service->restore($model);

        return $this->success(['message' => 'Brand Model restored successfully.']);
    }

    /**
     * Permanently delete a model.
     */
    public function forceDelete(Request $request, int $brandId, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $model = $this->service->find($id, $orgId, $brandId, withTrashed: true);
        $this->authorizeAction($request, $model);

        $this->service->forceDelete($model);

        return $this->deleted('Brand Model permanently deleted.');
    }
}
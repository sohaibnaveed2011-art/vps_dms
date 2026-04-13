<?php

namespace App\Http\Controllers\v1\api\Core;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Core\StoreWarehouseRequest;
use App\Http\Requests\Core\UpdateWarehouseRequest;
use App\Http\Resources\Core\WarehouseResource;
use App\Services\Core\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'core.warehouse.view',
        'store'       => 'core.warehouse.create',
        'show'        => 'core.warehouse.show',
        'update'      => 'core.warehouse.update',
        'destroy'     => 'core.warehouse.destroy',
        'forceDelete' => 'core.warehouse.forceDelete',
        'restore'     => 'core.warehouse.restore',
    ];

    public function __construct(protected WarehouseService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active']);
        $this->restrictToContext($request, $filters);

        $warehouses = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            WarehouseResource::collection($warehouses),
            $this->paginationMetadata($warehouses)
        );
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $this->authorizeAction($request);

        $this->enforcePolicy(
            $request,
            feature: 'core',
            limitResource: 'warehouse',
            modelClass: \App\Models\Core\Warehouse::class
        );

        // Auto-handles organization_id injection via BaseApiController
        $warehouse = $this->service->create($this->getValidatedData($request));

        return $this->created('Warehouse created successfully.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $warehouse = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $warehouse);

        return $this->success(new WarehouseResource($warehouse));
    }

    public function update(UpdateWarehouseRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $warehouse = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $warehouse);

        $this->service->update($warehouse, $request->validated());

        return $this->updated('Warehouse updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        // Permission check + Admin check handled by authorizeAction and logic
        $orgId = $this->getActiveOrgId($request);
        $warehouse = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $warehouse);

        $this->service->delete($id, $orgId);

        return $this->deleted('Warehouse deleted successfully.');
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $warehouse = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $warehouse);

        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Warehouse permanently deleted.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $warehouse = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $warehouse);

        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Warehouse restored successfully.']);
    }
}

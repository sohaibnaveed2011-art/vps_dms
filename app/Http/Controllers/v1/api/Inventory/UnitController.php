<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreUnitRequest;
use App\Http\Requests\Inventory\UpdateUnitRequest;
use App\Http\Resources\Inventory\UnitResource;
use App\Services\Inventory\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.unit.view',
        'store'       => 'inventory.unit.create',
        'show'        => 'inventory.unit.show',
        'update'      => 'inventory.unit.update',
        'destroy'     => 'inventory.unit.destroy',
        'restore'     => 'inventory.unit.restore',
        'forceDelete' => 'inventory.unit.forceDelete',
    ];

    public function __construct(protected UnitService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active']);

        // Mandatory Context Restriction
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            UnitResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    public function store(StoreUnitRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'inventory');

        // Automatically injects organization_id from user context
        $unit = $this->service->create($this->getValidatedData($request));

        return $this->created('Measuring unit created successfully...!');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $unit = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $unit);

        return $this->success(new UnitResource($unit));
    }

    public function update(UpdateUnitRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $unit = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $unit);
        $this->service->update($unit, $request->validated());

        return $this->updated('Unit updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $unit = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $unit);
        $this->service->delete($unit);

        return $this->deleted('Unit deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $unit = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $unit);

        $this->service->restore($unit);

        return $this->success(['message' => 'Unit restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $unit = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $unit);

        $this->service->forceDelete($unit);

        return $this->deleted('Unit permanently deleted.');
    }
}

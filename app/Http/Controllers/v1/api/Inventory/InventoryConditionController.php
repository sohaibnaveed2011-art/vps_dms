<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreInventoryConditionRequest;
use App\Http\Requests\Inventory\UpdateInventoryConditionRequest;
use App\Http\Resources\Inventory\InventoryConditionResource;
use App\Services\Inventory\Core\ConditionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryConditionController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.condition.view',
        'store'       => 'inventory.condition.create',
        'show'        => 'inventory.condition.show',
        'update'      => 'inventory.condition.update',
        'destroy'     => 'inventory.condition.destroy',
        'restore'     => 'inventory.condition.restore',
        'forceDelete' => 'inventory.condition.forceDelete',
    ];

    public function __construct(protected ConditionService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active']);
        $this->restrictToContext($request, $filters);

        $conditions = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            InventoryConditionResource::collection($conditions),
            $this->paginationMetadata($conditions)
        );
    }

    public function store(StoreInventoryConditionRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        $condition = $this->service->create($this->getValidatedData($request));

        return $this->created('Inventory condition created successfully...!');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $condition = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $condition);

        return $this->success(new InventoryConditionResource($condition));
    }

    public function update(UpdateInventoryConditionRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $condition = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $condition);
        $this->service->update($condition, $request->validated());

        return $this->updated('Condition updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $condition = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $condition);
        $this->service->delete($condition);

        return $this->deleted('Condition deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $condition = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $condition);

        $this->service->restore($condition);

        return $this->success(['message' => 'Condition restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $condition = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $condition);

        $this->service->forceDelete($condition);

        return $this->deleted('Condition permanently deleted.');
    }
}

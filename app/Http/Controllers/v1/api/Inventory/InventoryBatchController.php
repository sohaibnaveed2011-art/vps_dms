<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreInventoryBatchRequest;
use App\Http\Requests\Inventory\UpdateInventoryBatchRequest;
use App\Http\Resources\Inventory\InventoryBatchResource;
use App\Services\Inventory\InventoryBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryBatchController extends BaseApiController
{
    protected array $permissions = [
        'store'       => 'inventory.batch.create',
        'show'        => 'inventory.batch.show',
        'update'      => 'inventory.batch.update',
        'destroy'     => 'inventory.batch.delete',
        'restore'     => 'inventory.batch.restore',
        'forceDelete' => 'inventory.batch.forceDelete',
    ];

    public function __construct(protected InventoryBatchService $service)
    {
        parent::__construct();
    }

    public function store(StoreInventoryBatchRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        // Note: Variant/Org ownership should be validated in the FormRequest
        $batch = $this->service->create($request->validated());

        return $this->created(new InventoryBatchResource($batch));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $batch = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $batch);

        return $this->success(new InventoryBatchResource($batch));
    }

    public function update(UpdateInventoryBatchRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $batch = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $batch);
        $updated = $this->service->update($batch, $request->validated());

        return $this->success(new InventoryBatchResource($updated));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $batch = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $batch);
        $this->service->delete($batch);

        return $this->deleted('Batch deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $batch = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $batch);

        $this->service->restore($batch);

        return $this->success(['message' => 'Batch restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $batch = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $batch);

        $this->service->forceDelete($batch);

        return $this->deleted('Batch permanently deleted.');
    }
}

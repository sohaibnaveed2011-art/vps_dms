<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreVariationValueRequest;
use App\Http\Requests\Inventory\UpdateVariationValueRequest;
use App\Http\Resources\Inventory\VariationValueResource;
use App\Services\Inventory\VariationValueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariationValueController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.variationValue.view',
        'store'       => 'inventory.variationValue.create',
        'show'        => 'inventory.variationValue.show',
        'update'      => 'inventory.variationValue.update',
        'destroy'     => 'inventory.variationValue.destroy',
        'restore'     => 'inventory.variationValue.restore',
        'forceDelete' => 'inventory.variationValue.forceDelete',
    ];

    public function __construct(protected VariationValueService $service)
    {
        parent::__construct();
    }

    public function index(Request $request, int $variationId): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search']);
        $filters['variation_id'] = $variationId;

        // Ensure the variation itself belongs to the active organization context
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            VariationValueResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    public function store(StoreVariationValueRequest $request, int $variationId): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'inventory');

        $validated = $this->getValidatedData($request);

        $this->service->create(
            $validated['values'], 
            $variationId, 
            $validated['organization_id']
        );

        return $this->created('Variation Value created successfully...');
    }

    public function show(Request $request, int $variationId, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $value = $this->service->find($id, $orgId, $variationId);

        $this->authorizeAction($request, $value);

        return $this->success(new VariationValueResource($value));
    }

    public function update(UpdateVariationValueRequest $request, int $variationId, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $value = $this->service->find($id, $orgId, $variationId);

        $this->authorizeAction($request, $value);
        $this->service->update($value, $request->validated());

        return $this->updated('Variation Value updated successfully.');
    }

    public function destroy(Request $request, int $variationId, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $value = $this->service->find($id, $orgId, $variationId);

        $this->authorizeAction($request, $value);
        $this->service->delete($value);

        return $this->deleted('Variation value deleted successfully.');
    }

    public function restore(Request $request, int $variationId, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $value = $this->service->find($id, $orgId, $variationId, withTrashed: true);
        $this->authorizeAction($request, $value);

        $this->service->restore($value);

        return $this->success(['message' => 'Variation value restored successfully.']);
    }

    public function forceDelete(Request $request, int $variationId, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $value = $this->service->find($id, $orgId, $variationId, withTrashed: true);
        $this->authorizeAction($request, $value);

        $this->service->forceDelete($value);

        return $this->deleted('Variation value permanently deleted.');
    }
}

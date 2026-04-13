<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreVariationRequest;
use App\Http\Requests\Inventory\UpdateVariationRequest;
use App\Http\Resources\Inventory\VariationResource;
use App\Services\Inventory\VariationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariationController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.variation.view',
        'store'       => 'inventory.variation.create',
        'show'        => 'inventory.variation.show',
        'update'      => 'inventory.variation.update',
        'destroy'     => 'inventory.variation.destroy',
        'restore'     => 'inventory.variation.restore',
        'forceDelete' => 'inventory.variation.forceDelete',
    ];

    public function __construct(protected VariationService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active']);

        // Mandatory Context Restriction: Inject organization_id into filters
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            VariationResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    public function store(StoreVariationRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'inventory');

        // Automatically injects organization_id from the active context
        $variation = $this->service->create($this->getValidatedData($request));

        return $this->created('Variation created successfully...');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $variation = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $variation);

        return $this->success(new VariationResource($variation));
    }

    public function update(UpdateVariationRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $variation = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $variation);
        $this->service->update($variation, $request->validated());

        return $this->updated('Variation updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $variation = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $variation);
        $this->service->delete($variation);

        return $this->deleted('Variation deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $variation = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $variation);

        $this->service->restore($variation);

        return $this->success(['message' => 'Variation restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $variation = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $variation);

        $this->service->forceDelete($variation);

        return $this->deleted('Variation permanently deleted.');
    }
}

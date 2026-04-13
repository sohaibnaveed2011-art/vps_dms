<?php

namespace App\Http\Controllers\v1\api\Core;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Core\StoreTaxRequest;
use App\Http\Requests\Core\UpdateTaxRequest;
use App\Http\Resources\Core\TaxResource;
use App\Services\Core\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'core.tax.view',
        'store'       => 'core.tax.create',
        'show'        => 'core.tax.show',
        'update'      => 'core.tax.update',
        'destroy'     => 'core.tax.destroy',
        'forceDelete' => 'core.tax.forceDelete',
        'restore'     => 'core.tax.restore',
    ];

    public function __construct(protected TaxService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['is_active', 'search']);

        // Mandatory Context Restriction: Forces organization_id into filters
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            TaxResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    public function store(StoreTaxRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'core');
        // getValidatedData automatically injects active context org_id
        $tax = $this->service->create($this->getValidatedData($request));

        return $this->created('Tax created successfully.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        // Scoped lookup using current context org_id
        $tax = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $tax);
        return $this->success(new TaxResource($tax));
    }

    public function update(UpdateTaxRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $tax = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $tax);
        $this->service->update($tax, $request->validated());

        return $this->updated('Tax updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $tax = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $tax);
        $this->service->delete($id, $orgId);

        return $this->deleted('Tax deleted successfully.');
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $tax = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $tax);
        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Tax permanently deleted.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $tax = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $tax);
        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Tax restored successfully.']);
    }
}

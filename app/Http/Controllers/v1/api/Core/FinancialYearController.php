<?php

namespace App\Http\Controllers\v1\api\Core;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Core\StoreFinancialYearRequest;
use App\Http\Requests\Core\UpdateFinancialYearRequest;
use App\Http\Resources\Core\FinancialYearResource;
use App\Services\Core\FinancialYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialYearController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'core.financialYear.view',
        'store'       => 'core.financialYear.create',
        'show'        => 'core.financialYear.show',
        'update'      => 'core.financialYear.update',
        'destroy'     => 'core.financialYear.destroy',
        'forceDelete' => 'core.financialYear.forceDelete',
        'restore'     => 'core.financialYear.restore',
    ];

    public function __construct(protected FinancialYearService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['is_active']);

        // 1. Mandatory Context Restriction
        // This forces organization_id into $filters for non-admins
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            FinancialYearResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    public function store(StoreFinancialYearRequest $request): JsonResponse
    {
        $this->authorizeAction($request);

        // 2. Automated Org ID Injection
        // Injects organization_id from context if the user isn't a System Admin
        $fy = $this->service->create($this->getValidatedData($request));

        return $this->created('New financial year created..!');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        // 3. Scoped Find
        // getActiveOrgId returns NULL for System Admin (unfiltered)
        // and the current Context ID for everyone else.
        $orgId = $this->getActiveOrgId($request);
        $fy = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $fy);

        return $this->success(new FinancialYearResource($fy));
    }

    public function update(UpdateFinancialYearRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $fy = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $fy);
        $this->service->update($fy, $request->validated());

        return $this->updated('Financial Year updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $fy = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $fy);
        $this->service->delete($id, $orgId);

        return $this->deleted('Financial Year deleted successfully.');
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $fy = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $fy);
        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Financial Year permanently deleted.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $fy = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $fy);
        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Financial Year restored successfully.']);
    }
}

<?php

namespace App\Http\Controllers\v1\api\Core;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Core\StoreOutletRequest;
use App\Http\Requests\Core\UpdateOutletRequest;
use App\Http\Resources\Core\OutletResource;
use App\Services\Core\OutletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutletController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'core.outlet.view',
        'store'       => 'core.outlet.create',
        'show'        => 'core.outlet.show',
        'update'      => 'core.outlet.update',
        'destroy'     => 'core.outlet.destroy',
        'forceDelete' => 'core.outlet.forceDelete',
        'restore'     => 'core.outlet.restore',
    ];

    public function __construct(protected OutletService $service)
    {
        parent::__construct();
    }

    /**
     * List Outlets
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search']);
        $this->restrictToContext($request, $filters);

        $outlets = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            OutletResource::collection($outlets),
            $this->paginationMetadata($outlets)
        );
    }

    /**
     * Create Outlet
     */
    public function store(StoreOutletRequest $request): JsonResponse
    {
        $this->authorizeAction($request);

        $this->enforcePolicy(
            $request,
            feature: 'core',
            limitResource: 'outlet',
            modelClass: \App\Models\Core\Outlet::class
        );

        $outlet = $this->service->create($this->getValidatedData($request));

        return $this->created('Outlet created successfully.');
    }

    /**
     * Show Outlet
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $outlet = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $outlet);

        return $this->success(new OutletResource($outlet));
    }

    /**
     * Update Outlet
     */
    public function update(UpdateOutletRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $outlet = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $outlet);

        $this->service->update($outlet, $request->validated());

        return $this->updated('Outlet updated successfully.');
    }

    /**
     * Soft Delete Outlet
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $outlet = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $outlet);

        $this->service->delete($id, $orgId);

        return $this->deleted('Outlet deleted successfully.');
    }

    /**
     * Force Delete Outlet
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $outlet = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $outlet);

        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Outlet permanently deleted.');
    }

    /**
     * Restore Outlet
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $outlet = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $outlet);

        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Outlet restored successfully.']);
    }
}

<?php

namespace App\Http\Controllers\v1\api\Core;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Core\StoreOutletSectionRequest;
use App\Http\Requests\Core\UpdateOutletSectionRequest;
use App\Http\Resources\Core\OutletSectionResource;
use App\Services\Core\OutletSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutletSectionController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'core.outletSection.view',
        'store'       => 'core.outletSection.create',
        'show'        => 'core.outletSection.show',
        'update'      => 'core.outletSection.update',
        'destroy'     => 'core.outletSection.destroy',
        'restore'     => 'core.outletSection.restore',
        'forceDelete' => 'core.outletSection.forceDelete',
    ];

    public function __construct(protected OutletSectionService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['organization_id','outlet_id', 'is_pos_counter', 'is_active', 'search']);
        $this->restrictToContext($request, $filters);

        $sections = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            OutletSectionResource::collection($sections),
            $this->paginationMetadata($sections)
        );
    }

    public function store(StoreOutletSectionRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'core');

        $section = $this->service->create($this->getValidatedData($request));

        return $this->created('Outlet Section created successfully.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $section = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $section);

        return $this->success(new OutletSectionResource($section));
    }

    public function update(UpdateOutletSectionRequest $request, int $id): JsonResponse
    {
        $section = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $section);
        $this->service->update($section, $request->validated());

        return $this->updated('Outlet Section updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $section = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $section);
        $this->service->delete($id, $orgId);

        return $this->deleted('Outlet Section deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);
        $section = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $section);
        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Outlet Section restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $section = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $section);
        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Outlet Section permanently deleted.');
    }
}

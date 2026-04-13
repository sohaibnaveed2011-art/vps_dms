<?php

namespace App\Http\Controllers\v1\api\Core;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Core\StoreWarehouseSectionRequest;
use App\Http\Requests\Core\UpdateWarehouseSectionRequest;
use App\Http\Resources\Core\WarehouseSectionResource;
use App\Services\Core\WarehouseSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseSectionController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'core.warehouseSection.view',
        'store'       => 'core.warehouseSection.create',
        'show'        => 'core.warehouseSection.show',
        'update'      => 'core.warehouseSection.update',
        'destroy'     => 'core.warehouseSection.destroy',
        'forceDelete' => 'core.warehouseSection.forceDelete',
        'restore'     => 'core.warehouseSection.restore',
    ];

    public function __construct(protected WarehouseSectionService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['warehouse_id', 'search', 'is_active', 'parent_section_id']);
        $this->restrictToContext($request, $filters);

        $sections = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            WarehouseSectionResource::collection($sections),
            $this->paginationMetadata($sections)
        );
    }

    public function store(StoreWarehouseSectionRequest $request): JsonResponse
    {
        $this->authorizeAction($request);

        $this->enforcePolicy($request, feature: 'core');

        $section = $this->service->create($this->getValidatedData($request));

        return $this->created('Warehouse Section created successfully.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $section = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $section);

        return $this->success(new WarehouseSectionResource($section));
    }

    public function update(UpdateWarehouseSectionRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $section = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $section);
        $this->service->update($section, $request->validated());

        return $this->updated('Warehouse Section updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $section = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $section);
        $this->service->delete($id, $orgId);

        return $this->deleted('Warehouse Section deleted successfully.');
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $section = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $section);
        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Warehouse Section permanently deleted.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $section = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $section);
        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Warehouse Section restored successfully.']);
    }
}

<?php

namespace App\Http\Controllers\v1\api\Partner;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Partner\StorePartnerCategoryRequest;
use App\Http\Requests\Partner\UpdatePartnerCategoryRequest;
use App\Http\Resources\Partner\PartnerCategoryResource;
use App\Services\Partner\PartnerCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerCategoryController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'partner.partnerCategory.view',
        'store'       => 'partner.partnerCategory.create',
        'show'        => 'partner.partnerCategory.show',
        'update'      => 'partner.partnerCategory.update',
        'destroy'     => 'partner.partnerCategory.destroy',
        'forceDelete' => 'partner.partnerCategory.forceDelete',
        'restore'     => 'partner.partnerCategory.restore',
    ];

    public function __construct(protected PartnerCategoryService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $type = $request->route('category_type');
        if (!in_array($type, ['customer', 'supplier'])) {
            throw new NotFoundException('Invalid category type.');
        }

        $filters = $request->only(['organization_id','is_active', 'search']);
        $filters['type'] = $type;

        // Mandatory Context Restriction
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            PartnerCategoryResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    public function store(StorePartnerCategoryRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'partners');

        // Automated Org ID Injection via getValidatedData
        $category = $this->service->create($this->getValidatedData($request));

        return $this->created('Partner Category created successfully..');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $category = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $category);

        return $this->success(new PartnerCategoryResource($category));
    }

    public function update(UpdatePartnerCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $category);
        $this->service->update($category, $request->validated());

        return $this->updated('Partner Category updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $category);
        $this->service->delete($category);

        return $this->deleted('Partner Category deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $category);
        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Partner Category restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $category = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $category);

        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Partner category permanently deleted.');
    }
}

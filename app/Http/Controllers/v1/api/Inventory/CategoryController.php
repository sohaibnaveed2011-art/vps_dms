<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreCategoryRequest;
use App\Http\Requests\Inventory\UpdateCategoryRequest;
use App\Http\Resources\Inventory\CategoryResource;
use App\Services\Inventory\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.category.view',
        'store'       => 'inventory.category.create',
        'show'        => 'inventory.category.show',
        'update'      => 'inventory.category.update',
        'destroy'     => 'inventory.category.destroy',
        'restore'     => 'inventory.category.restore',
        'forceDelete' => 'inventory.category.forceDelete',
    ];

    public function __construct(protected CategoryService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['parent_id', 'is_active', 'search']);

        // Mandatory Context Restriction: Forces organization_id into filters array
        $this->restrictToContext($request, $filters);

        $categories = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            CategoryResource::collection($categories),
            $this->paginationMetadata($categories)
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'inventory');

        // Automatically injects organization_id from user context
        $category = $this->service->create($this->getValidatedData($request));

        return $this->created('Category created successfully.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $category);

        return $this->success(new CategoryResource($category));
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $category);
        $this->service->update($category, $request->validated());

        return $this->updated('Category updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $category);
        $this->service->delete($category);

        return $this->deleted('Category deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $category = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $category);

        $this->service->restore($category);

        return $this->success(['message' => 'Category restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $category = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $category);

        $this->service->forceDelete($category);

        return $this->deleted('Category permanently deleted.');
    }
}

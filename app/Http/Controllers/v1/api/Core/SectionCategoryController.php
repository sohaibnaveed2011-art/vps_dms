<?php

namespace App\Http\Controllers\v1\api\Core;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Core\StoreSectionCategoryRequest;
use App\Http\Requests\Core\UpdateSectionCategoryRequest;
use App\Http\Resources\Core\SectionCategoryResource;
use App\Services\Core\SectionCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionCategoryController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'core.sectionCategory.view',
        'store'       => 'core.sectionCategory.create',
        'show'        => 'core.sectionCategory.show',
        'update'      => 'core.sectionCategory.update',
        'destroy'     => 'core.sectionCategory.destroy',
        'forceDelete' => 'core.sectionCategory.forceDelete',
        'restore'     => 'core.sectionCategory.restore',
    ];

    public function __construct(protected SectionCategoryService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of section categories.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active']);
        $this->restrictToContext($request, $filters);

        $categories = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            SectionCategoryResource::collection($categories),
            $this->paginationMetadata($categories)
        );
    }

    /**
     * Store a newly created section category.
     */
    public function store(StoreSectionCategoryRequest $request): JsonResponse
    {
        $this->authorizeAction($request);

        $this->enforcePolicy($request, feature: 'core');

        $category = $this->service->create($this->getValidatedData($request));

        return $this->created('Section Category created successfully.');
    }

    /**
     * Display the specified section category.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $category);

        return $this->success(new SectionCategoryResource($category));
    }

    /**
     * Update the specified section category.
     */
    public function update(UpdateSectionCategoryRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $category);

        $this->service->update($category, $request->validated());

        return $this->updated('Section Category updated successfully.');
    }

    /**
     * Remove the specified section category (Soft Delete).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $category);

        $this->service->delete($id, $orgId);

        return $this->deleted('Section Category deleted successfully.');
    }

    /**
     * Permanently delete the section category.
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $category);

        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Section Category permanently deleted.');
    }

    /**
     * Restore a soft-deleted section category.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $category = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $category);

        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Section Category restored successfully.']);
    }
}

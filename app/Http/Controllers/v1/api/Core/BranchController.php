<?php

namespace App\Http\Controllers\v1\api\Core;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Core\StoreBranchRequest;
use App\Http\Requests\Core\UpdateBranchRequest;
use App\Http\Resources\Core\BranchResource;
use App\Services\Core\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'core.branch.view',
        'store'       => 'core.branch.create',
        'show'        => 'core.branch.show',
        'update'      => 'core.branch.update',
        'destroy'     => 'core.branch.destroy',
        'forceDelete' => 'core.branch.forceDelete',
        'restore'     => 'core.branch.restore',
    ];

    public function __construct(protected BranchService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the branches.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active']);
        $this->restrictToContext($request, $filters);

        $branches = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            BranchResource::collection($branches),
            $this->paginationMetadata($branches)
        );
    }

    /**
     * Store a newly created branch.
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $this->authorizeAction($request);

        $this->enforcePolicy(
            $request,
            feature: 'core',
            limitResource: 'branch',
            modelClass: \App\Models\Core\Branch::class
        );

        $branch = $this->service->create($this->getValidatedData($request));

        return $this->created('Branch created successfully.');
    }

    /**
     * Display the specified branch.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        // Scope the find by OrgId for security
        $branch = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $branch);

        return $this->success(new BranchResource($branch->load('organization')));
    }

    /**
     * Update the specified branch.
     */
    public function update(UpdateBranchRequest $request, int $id): JsonResponse
    {
        $branch = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $branch);

        $this->service->update($branch, $request->validated());

        return $this->updated('Branch updated successfully.');
    }

    /**
     * Remove the specified branch (Soft Delete).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $branch = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $branch);

        $this->service->delete($id, $orgId);

        return $this->deleted('Branch deleted successfully.');
    }

    /**
     * Permanently delete the branch.
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        $branch = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $branch);

        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Branch permanently deleted.');
    }

    /**
     * Restore a soft-deleted branch.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $orgId = $this->getActiveOrgId($request);
        // We find it first to authorize the specific instance
        $branch = $this->service->find($id, $orgId, withTrashed: true);

        $this->authorizeAction($request, $branch);

        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Branch restored successfully.']);
    }
}

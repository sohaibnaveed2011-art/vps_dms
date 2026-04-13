<?php

namespace App\Http\Controllers\v1\api\Core;

use App\Exceptions\ForbiddenException;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Core\StoreOrganizationRequest;
use App\Http\Requests\Core\UpdateOrganizationRequest;
use App\Http\Resources\Core\OrganizationResource;
use App\Services\Core\OrganizationService;
use Illuminate\Http\Request;

class OrganizationController extends BaseApiController
{
    /**
     * No $permissions array needed if we use ensureAdmin
     * globally or per method.
     */

    public function __construct(protected OrganizationService $service)
    {
        parent::__construct();
    }

    /* =========================================================
     | List
     ========================================================= */

    public function index(Request $request)
    {
        // 1. Check if the user has the general permission to view organizations
        // $this->authorizeAction($request);
        $filters = $request->only(['search', 'is_active']);
        // 2. Apply the "Identity Gap" logic
        if (!$request->user()->is_admin) {
            // Force the filter to ONLY their organization ID
            // restrictToContext usually injects organization_id into $filters automatically
            $this->restrictToContext($request, $filters);
            // If your restrictToContext helper uses 'organization_id' but your service
            // uses 'id' for the Organization index, map it here:
            if (isset($filters['organization_id'])) {
                $filters['id'] = $filters['organization_id'];
                unset($filters['organization_id']);
            }
        }
        // 3. Super Admins bypass the block above and get all results based on filters
        $organizations = $this->service->paginate($filters, $this->perPage($request));
        return $this->success(
            OrganizationResource::collection($organizations),
            $this->paginationMetadata($organizations)
        );
}

    /* =========================================================
     | Create
     ========================================================= */

    public function store(StoreOrganizationRequest $request)
    {
        $this->ensureAdmin($request);

        $org = $this->service->create($request->validated());

        return $this->created('Organization created successfully.');
    }

    /* =========================================================
     | Show
     ========================================================= */

    public function show(Request $request, int $id)
    {
        $this->ensureAdmin($request);

        $org = $this->service->find($id);

        return $this->success(new OrganizationResource($org));
    }

    /* =========================================================
     | Update
     ========================================================= */

    public function update(UpdateOrganizationRequest $request, int $id)
    {
        $this->ensureAdmin($request);

        $org = $this->service->find($id);

        // System rule: Prevent editing certain fields if policies are locked
        if ($org->policies_locked) {
            throw new ForbiddenException('This organization is locked. You must unlock it via the Unlock API before editing profile details.');
        }
        $this->service->update($org, $request->validated());
        return $this->updated('Organization updated successfully.');
    }

    /* =========================================================
     | Delete Operations
     ========================================================= */

    public function destroy(Request $request, int $id)
    {
        $this->ensureAdmin($request);

        $org = $this->service->find($id);
        $this->service->delete($org);

        return $this->deleted('Organization deleted successfully.');
    }

    public function restore(Request $request, int $id)
    {
        $this->ensureAdmin($request);

        $this->service->restore($id);

        return $this->success(null, ['message' => 'Organization restored successfully.']);
    }

    /* =========================================================
     | Global Policy Locking
     ========================================================= */

    public function lockAll(Request $request, int $id)
    {
        $this->ensureAdmin($request);

        $this->service->lockOrganizationPolicies($id);

        return $this->success(null, ['message' => 'Organization policies locked.']);
    }

    public function unlockAll(Request $request, int $id)
    {
        $this->ensureAdmin($request);
        $this->service->unlockOrganizationPolicies($id);
        return $this->success(null, ['message' => 'Organization policies unlocked.']);
    }
}

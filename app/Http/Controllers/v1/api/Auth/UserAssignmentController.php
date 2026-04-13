<?php

namespace App\Http\Controllers\v1\api\Auth;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Resources\Auth\UserAssignmentResource;
use App\Services\Auth\UserAssignmentService;
use Illuminate\Http\Request;

class UserAssignmentController extends BaseApiController
{
    protected array $permissions = [
        'index'                 => 'user.assignment.view',
        'show'                  => 'user.assignment.show',
        'assignRoleAt'          => 'user.assignment.create',
        'revokeRoleAt'          => 'user.assignment.destroy',
    ];

    public function __construct(
        protected UserAssignmentService $service
    ) {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $this->authorizeAction($request);

        // 1. Collect filters from request
        $filters = $request->only(['user_id']);

        // 2. IMPORTANT: If this is NOT an admin, restrict assignments to their Org
        // This prevents a tenant from seeing another tenant's user assignments
        $this->restrictToContext($request, $filters);

        // 3. Call the unified service method
        $assignments = $this->service->paginate(
            $filters,
            $this->perPage($request)
        );

        // 4. Return using standardized helpers
        return $this->success(
            UserAssignmentResource::collection($assignments),
            $this->paginationMetadata($assignments)
        );
    }

    public function show(Request $request, int $id)
    {
        $assignment = $this->service->find($id)
            ?? throw new NotFoundException( 'User assignment not found');

        $this->authorizeAction($request, $assignment);

        return $this->success(new UserAssignmentResource($assignment));
    }

    public function assignRoleAt(Request $request, int $userId)
    {
        $this->authorizeAction($request);

        $assignment = $this->service->assignRoleAt(
            $userId,
            $request->role_id,
            $request->assignable_type,
            $request->assignable_id,
            $request->user()
        );

        return $this->created(['message' => 'User role assigned successfully.']);
    }

    /**
     * Revoke (deactivate) a user assignment.
     */
    public function revokeRoleAt(Request $request, int $assignmentId)
    {
        $this->authorizeAction($request);

        $assignment = $this->service->revokeById(
            assignmentId: $assignmentId,
            actor: $request->user()
        );

        return $this->success(
            ['message' => 'User role revoked successfully.']
        );
    }

    public function myActiveAssignments(Request $request)
    {
        $user = $request->user();
        $assignments = $this->service->getActiveAssignments($user->id);

        return $this->success(
            $assignments->map(fn($a) => [
                'assignment_id'   => $a->id,
                'role'            => $a->role->name,
                // 🔥 CLEANER: Uses the Morph Map key directly (e.g., 'branch')
                'scope_type'      => $a->assignable_type,
                'scope_id'        => $a->assignable_id,
                'organization_id' => $a->assignable->organization_id ?? null,
                'label'           => ucfirst($a->assignable_type),
            ])
        );
    }
}

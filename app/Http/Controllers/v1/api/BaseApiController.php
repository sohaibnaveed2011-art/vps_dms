<?php

namespace App\Http\Controllers\v1\api;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Guards\PolicyGuard;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthorizationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    /**
     * Common services shared across all API controllers.
     * Resolved via container to avoid constructor bloat in children.
     */
    protected AuthorizationService $authorization;
    protected PolicyGuard $policyGuard;
    /**
     * ---------------------------------------------------------
     * Action → Permission Mapping
     * ---------------------------------------------------------
     *
     * Example:
     * [
     *   'index'   => 'core.organization.view',
     *   'store'   => 'core.organization.create',
     *   'show'    => 'core.organization.show',
     *   'update'  => 'core.organization.update',
     *   'destroy' => 'core.organization.delete',
     *
     *   Explicit opt-out (no authorization required)
     *   'health'  => null,
     * ]
     */
    protected array $permissions = [];

    public function __construct() {
        $this->authorization = app(AuthorizationService::class);
        $this->policyGuard = app(PolicyGuard::class);
    }

    /**
     * 1. The Core Resolver (Single Source of Truth)
     */
    protected function context(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            throw new UnauthorizedException('Unauthenticated.');
        }

        // Admins get a null context (bypass)
        if ($user->is_admin) {
            return null;
        }

        $context = $user->activeContext();

        if (!$context) {
            throw new ForbiddenException('No active context selected.');
        }

        return $context;
    }

    /**
     * 2. The ID Extractor
     * Simplified: Just extracts the ID from the context if it exists.
     */
    protected function getActiveOrgId(Request $request): ?int
    {
        return $this->context($request)?->organization_id;
    }

    /**
     * 3. The Filter Applier
     * Simplified: Uses the ID extractor to modify the array.
     */
    protected function restrictToContext(Request $request, array &$filters): void
    {
        if ($orgId = $this->getActiveOrgId($request)) {
            $filters['organization_id'] = $orgId;
        }
    }

    /**
     * Helper to get validated data and automatically inject organization_id.
     */
    protected function getValidatedData(Request $request): array
    {
        $data = $request->validated();

        // If it's not an admin, we force the organization_id from their context
        if ($orgId = $this->getActiveOrgId($request)) {
            $data['organization_id'] = $orgId;
        }

        return $data;
    }

    /**
     * Orchestrates multiple policy checks: Feature, Hierarchy, and Limits.
     * Automatically bypasses all checks if user is_admin.
     */
    protected function enforcePolicy(
        Request $request,
        ?string $feature = null,
        ?string $limitResource = null,
        ?string $modelClass = null
    ): void {
        $user = $request->user();

        // 🔥 FULL SYSTEM ADMIN BYPASS
        if ($user && $user->is_admin) {
            return;
        }

        $orgId = $this->getActiveOrgId($request);

        // 1. Feature Check (e.g., 'inventory')
        if ($feature) {
            $this->policyGuard->requireFeature($orgId, $feature);
        }

        // 2. Hierarchy Check (Only for specific core resources)
        if (in_array($limitResource, ['branch', 'warehouse', 'outlet'])) {
            $this->policyGuard->requireHierarchy($orgId, $limitResource);
        }

        // 3. Resource Usage Limit Check
        if ($limitResource && $modelClass) {
            $currentCount = $modelClass::query()
                ->where('organization_id', $orgId)
                ->when(
                    in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($modelClass)),
                    fn ($q) => $q->whereNull('deleted_at')
                )
                ->count();

            $this->policyGuard->requireWithinLimit($orgId, $limitResource, $currentCount);
        }
    }

    /**
     * Standardized pagination metadata extractor.
     */
    protected function paginationMetadata(LengthAwarePaginator $paginated): array
    {
        return [
            'total' => $paginated->total(),
            'per_page' => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ];
    }

    /**
     * Standardized pagination resolver.
     */
    protected function perPage(Request $request): int
    {
        return min(
            max((int) $request->input('per_page', 15), 1),
            100
        );
    }

    /**
     * Ensure the current authenticated user is system admin.
     */
    protected function ensureAdmin(
        Request $request,
        string $message = 'Forbidden.'
    ): void {
        $user = $request->user();

        if (! $user || ! $user->is_admin) {
            throw new ForbiddenException($message);
        }
    }

    /* =========================================================
     | Authorization Layer
     ========================================================= */

    /**
     * Authorize current controller action.
     *
     * - Requires authenticated user
     * - Resolves permission by action name
     * - Delegates rule enforcement to AuthorizationService
     *
     * IMPORTANT:
     * This is the ONLY authorization checkpoint at controller level.
     */
    protected function authorizeAction(Request $request, mixed $target = null): void
    {
        $user = $request->user();

        if (! $user) {
            throw new UnauthorizedException('Unauthenticated.');
        }

        // 🔥 FULL SYSTEM ADMIN BYPASS
        if ($user->is_admin) {
            return;
        }

        $action = $request->route()?->getActionMethod();

        if (! $action) {
            throw new ForbiddenException('Unauthorized action.');
        }

        if (! array_key_exists($action, $this->permissions)) {
            throw new ForbiddenException(
                "Permission not defined for action [{$action}]."
            );
        }

        // Explicit opt-out
        if ($this->permissions[$action] === null) {
            return;
        }

        $this->authorization->authorize(
            $user,
            $this->permissions[$action],
            $target
        );
    }

    /* =========================================================
     | Success Response Helpers
     ========================================================= */

    protected function success(
        mixed $data = null,
        array $meta = [],
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => empty($meta) ? null : $meta,
            'errors' => null,
        ], $status);
    }

    protected function created(
        mixed $data = null,
        array $meta = []
    ): JsonResponse {
        return $this->success($data, $meta, 201);
    }

    protected function updated(
        string $message = 'Resource updated successfully.'
    ): JsonResponse {
        return $this->success(null, ['message' => $message], 200);
    }

    protected function deleted(
        string $message = 'Resource deleted successfully.'
    ): JsonResponse {
        return $this->success(null, ['message' => $message]);
    }
}

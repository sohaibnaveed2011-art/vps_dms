<?php

namespace App\Http\Controllers\v1\api\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Resources\Inventory\PromotionResource;
use App\Services\Inventory\Pricing\PromotionService;
use App\Http\Requests\Inventory\StorePromotionRequest;
use App\Http\Requests\Inventory\UpdatePromotionRequest;

class PromotionController extends BaseApiController
{
    protected array $permissions = [
        'index'             => 'inventory.promotion.view',
        'store'             => 'inventory.promotion.create',
        'show'              => 'inventory.promotion.show',
        'update'            => 'inventory.promotion.update',
        'destroy'           => 'inventory.promotion.destroy',
        'restore'           => 'inventory.promotion.restore',
        'forceDelete'       => 'inventory.promotion.forceDelete',
        'validatePromotion' => 'inventory.promotion.view',
        'applyPromotion'    => 'inventory.promotion.update',
        'syncScopes'        => 'inventory.promotion.update',
        'syncTargets'       => 'inventory.promotion.update',
        'statistics'        => 'inventory.promotion.view',
        'duplicate'         => 'inventory.promotion.create',
        'toggleStatus'      => 'inventory.promotion.update',
        'bulkAssignScopes'  => 'inventory.promotion.update',
    ];

    public function __construct(protected PromotionService $service)
    {
        parent::__construct();
    }

    /**
     * Display a paginated list of promotions.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active', 'type', 'stackable', 'start_date', 'end_date']);
        $this->restrictToContext($request, $filters);

        $promotions = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            PromotionResource::collection($promotions),
            $this->paginationMetadata($promotions)
        );
    }

    /**
     * Create a new promotion.
     */
    public function store(StorePromotionRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        $data = $request->validated();
        $data['organization_id'] = $this->getActiveOrgId($request);
        $data['created_by'] = $request->user()?->id;

        $promotion = $this->service->create($data);

        return $this->created('Promotion created successfully.');
    }

    /**
     * Display the specified promotion.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $promotion = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $promotion);

        return $this->success(new PromotionResource($promotion->load(['scopes.scopeable', 'targets.targetable'])));
    }

    /**
     * Update the specified promotion.
     */
    public function update(UpdatePromotionRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $promotion = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $promotion);
        $updated = $this->service->update($promotion, $request->validated());

        return $this->success(new PromotionResource($updated));
    }

    /**
     * Soft delete the specified promotion.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $promotion = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $promotion);
        $this->service->delete($promotion);

        return $this->deleted('Promotion deleted successfully.');
    }

    /**
     * Restore a soft-deleted promotion.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        // $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $promotion = $this->service->find($id, $orgId, true);
        $this->authorizeAction($request, $promotion);
        $this->service->restore($promotion);

        return $this->success(['message' => 'Promotion restored successfully.']);
    }

    /**
     * Permanently delete a promotion.
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $promotion = $this->service->find($id, $orgId, true);
        $this->authorizeAction($request, $promotion);
        $this->service->forceDelete($promotion);

        return $this->deleted('Promotion permanently deleted.');
    }

    // ==================== VALIDATION & APPLICATION ====================

    /**
     * Validate a promotion without applying it.
     */
    public function validatePromotion(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $request->validate([
            'promotion_id' => 'required|exists:promotions,id',
            'subtotal' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $orgId = (int) $this->getActiveOrgId($request);
        
        $validation = $this->service->validatePromotion(
            $request->input('promotion_id'),
            $orgId,
            (float) $request->input('subtotal'),
            $request->input('customer_id')
        );

        return $this->success($validation);
    }

    /**
     * Apply a promotion and calculate discount.
     */
    public function applyPromotion(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $request->validate([
            'promotion_id' => 'required|exists:promotions,id',
            'subtotal' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $orgId = (int) $this->getActiveOrgId($request);
        
        $result = $this->service->applyPromotion(
            $request->input('promotion_id'),
            $orgId,
            (float) $request->input('subtotal'),
            $request->input('customer_id')
        );

        return $this->success($result);
    }

    // ==================== SYNC OPERATIONS ====================

    /**
     * Sync scopes for a promotion.
     */
    public function syncScopes(Request $request, int $id): JsonResponse
    {
        $this->authorizeAction($request);

        $request->validate([
            'scopes' => 'required|array',
            'scopes.*.scopeable_type' => 'required|string',
            'scopes.*.scopeable_id' => 'required|integer|min:1',
            'sync_mode' => 'nullable|in:replace,merge,remove',
        ]);

        $orgId = $this->getActiveOrgId($request);
        $promotion = $this->service->find($id, $orgId);

        $this->service->syncScopes(
            $promotion, 
            $request->input('scopes'), 
            $request->input('sync_mode', 'replace')
        );

        return $this->success('Scopes synced successfully.');
    }

    /**
     * Sync targets for a promotion.
     */
    public function syncTargets(Request $request, int $id): JsonResponse
    {
        $this->authorizeAction($request);

        $request->validate([
            'targets' => 'required|array',
            'targets.*.targetable_type' => 'required|string',
            'targets.*.targetable_id' => 'required|integer|min:1',
            'sync_mode' => 'nullable|in:replace,merge,remove',
        ]);

        $orgId = $this->getActiveOrgId($request);
        $promotion = $this->service->find($id, $orgId);

        $this->service->syncTargets(
            $promotion, 
            $request->input('targets'), 
            $request->input('sync_mode', 'replace')
        );

        return $this->success('Targets synced successfully.');
    }

    // ==================== QUERIES & STATISTICS ====================

    /**
     * Get promotion statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $stats = $this->service->getStatistics($orgId);

        return $this->success($stats);
    }

    /**
     * Get active promotions for a specific scope/target.
     */
    public function active(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $request->validate([
            'scope_type' => 'required|string',
            'scope_id' => 'required|integer',
            'target_type' => 'nullable|string',
            'target_id' => 'nullable|integer|required_with:target_type',
        ]);

        $promotions = $this->service->getActivePromotions(
            $request->input('scope_type'),
            $request->input('scope_id'),
            $request->input('target_type'),
            $request->input('target_id')
        );

        return $this->success(PromotionResource::collection($promotions));
    }

    /**
     * Get best applicable promotions for a cart.
     */
    public function bestApplicable(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $request->validate([
            'scope_type' => 'required|string',
            'scope_id' => 'required|integer',
            'subtotal' => 'required|numeric|min:0',
            'target_type' => 'nullable|string',
            'target_id' => 'nullable|integer|required_with:target_type',
        ]);

        $promotions = $this->service->getBestApplicablePromotions(
            $request->input('scope_type'),
            $request->input('scope_id'),
            (float) $request->input('subtotal'),
            $request->input('target_type'),
            $request->input('target_id')
        );

        return $this->success($promotions);
    }

    // ==================== MANAGEMENT OPERATIONS ====================

    /**
     * Duplicate an existing promotion.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $promotion = $this->service->find($id, $orgId);

        $duplicated = $this->service->duplicate($promotion, $request->user()?->id);

        return $this->created('Promotion duplicated successfully.');
    }

    /**
     * Toggle promotion active status.
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $promotion = $this->service->find($id, $orgId);

        $updated = $this->service->toggleStatus($promotion);

        return $this->success([
            'id' => $updated->id,
            'is_active' => $updated->is_active,
        ]);
    }

    /**
     * Bulk assign scopes to multiple promotions.
     */
    public function bulkAssignScopes(Request $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->ensureAdmin($request);

        $request->validate([
            'promotion_ids' => 'required|array|min:1',
            'promotion_ids.*' => 'exists:promotions,id',
            'scopes' => 'required|array|min:1',
            'scopes.*.scopeable_type' => 'required|string',
            'scopes.*.scopeable_id' => 'required|integer',
            'sync_mode' => 'nullable|in:replace,merge,remove',
        ]);

        $result = $this->service->bulkAssignScopes(
            $request->input('promotion_ids'),
            $request->input('scopes'),
            $request->input('sync_mode', 'merge')
        );

        return $this->success($result);
    }
}
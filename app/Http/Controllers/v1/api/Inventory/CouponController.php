<?php

namespace App\Http\Controllers\v1\api\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Inventory\CouponResource;
use App\Services\Inventory\Pricing\CouponService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreCouponRequest;
use App\Http\Requests\Inventory\UpdateCouponRequest;
use App\Models\Inventory\Coupon;

class CouponController extends BaseApiController
{
    protected array $permissions = [
        'index'             =>  'inventory.coupon.view',
        'store'             =>  'inventory.coupon.create',
        'show'              =>  'inventory.coupon.show',
        'update'            =>  'inventory.coupon.update',
        'destroy'           =>  'inventory.coupon.destroy',
        'restore'           =>  'inventory.coupon.restore',
        'forceDelete'       =>  'inventory.coupon.forceDelete',
        'validateCoupon'    =>  'inventory.coupon.view',
        'applyCoupon'       =>  'inventory.coupon.update', 
        'bulkAssign'        =>  'inventory.coupon.create',
        'statistics'        =>  'inventory.coupon.view',
        'duplicate'         =>  'inventory.coupon.create',
        'toggleStatus'      =>  'inventory.coupon.update',
        'customerCoupons'   =>  'inventory.coupon.view',
        'syncScopes'        =>  'inventory.coupon.update',
        'syncTargets'       =>  'inventory.coupon.update',
    ];

    public function __construct(protected CouponService $service)
    {
        parent::__construct();
    }

    /**
     * Display a paginated list of coupons.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'code', 'is_active', 'type']);
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            CouponResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    /**
     * Create a new coupon.
     */
    public function store(StoreCouponRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        $data = $request->validated();
        $data['organization_id'] = $this->getActiveOrgId($request);
        $data['created_by'] = $request->user()?->id;

        $coupon = $this->service->create($data);

        return $this->created('Coupon created successfully.');
    }

    /**
     * Display the specified coupon.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $coupon = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $coupon);

        return $this->success(new CouponResource($coupon->load(['scopes.scopeable', 'targets.targetable', 'customers'])));
    }

    /**
     * Update the specified coupon.
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $coupon = $this->service->find($coupon->id, $orgId);

        $this->authorizeAction($request, $coupon);
        
        $updated = $this->service->update($coupon, $request->validated());

        return $this->success('Coupon updated successfully.');
    }

    /**
     * Soft delete the specified coupon.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $coupon = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $coupon);
        $this->service->delete($coupon);

        return $this->deleted('Coupon deleted successfully.');
    }

    /**
     * Restore a soft-deleted coupon.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $coupon = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $coupon);
        $this->service->restore($coupon);

        return $this->success(['message' => 'Coupon restored successfully.']);
    }

    /**
     * Permanently delete a coupon.
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $coupon = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $coupon);
        $this->service->forceDelete($coupon);

        return $this->deleted('Coupon permanently deleted.');
    }

    /**
     * Validate a coupon without applying it.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $orgId = (int) $this->getActiveOrgId($request);

        $validation = $this->service->validateCoupon(
            $request->input('code'),
            $orgId,
            (float) $request->input('subtotal'),
            $request->input('customer_id')
        );

        return $this->success($validation);
    }

    /**
     * Apply a coupon and calculate discount.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $orgId = (int) $this->getActiveOrgId($request);
        
        $result = $this->service->applyCoupon(
            $request->input('code'),
            $orgId,
            (float) $request->input('subtotal'),
            $request->input('customer_id')
        );

        return $this->success($result);
    }

    /**
     * Bulk assign coupon to multiple customers.
     */
    public function bulkAssign(Request $request, int $id): JsonResponse
    {
        $this->authorizeAction($request);
        // $this->ensureAdmin($request);

        $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id',
            'sync_mode' => 'nullable|in:replace,merge,remove',
        ]);

        $orgId = $this->getActiveOrgId($request);
        $coupon = $this->service->find($id, $orgId);

        $result = $this->service->bulkAssignToCustomers(
            $coupon->id, 
            $request->input('customer_ids'),
            $request->input('sync_mode', 'merge')
        );

        return $this->success($result);
    }

    /**
     * Sync scopes for a coupon.
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
        $coupon = $this->service->find($id, $orgId);

        $updated = $this->service->syncScopes(
            $coupon, 
            $request->input('scopes'), 
            $request->input('sync_mode', 'replace')
        );

        return $this->success('Scopes synced successfully.');
    }

    /**
     * Sync targets for a coupon.
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
        $coupon = $this->service->find($id, $orgId);

        $updated = $this->service->syncTargets(
            $coupon, 
            $request->input('targets'), 
            $request->input('sync_mode', 'replace')
        );

        return $this->success('Targets synced successfully.');
    }

    /**
     * Get coupon statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $stats = $this->service->getStatistics($orgId);

        return $this->success($stats);
    }

    /**
     * Duplicate an existing coupon.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $coupon = $this->service->find($id, $orgId);

        $duplicated = $this->service->duplicate($coupon, $request->user()?->id);

        return $this->created('Coupon duplicated successfully.');
    }

    /**
     * Toggle coupon active status.
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $coupon = $this->service->find($id, $orgId);

        $updated = $this->service->toggleStatus($coupon);

        return $this->success([
            'id' => $updated->id,
            'is_active' => $updated->is_active,
        ]);
    }

    /**
     * Get coupons assigned to a specific customer.
     */
    public function customerCoupons(Request $request, int $customerId): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        
        $coupons = $this->service->getCustomerCoupons(
            $customerId,
            $orgId,
            $request->input('only_available', false)
        );

        return $this->success(CouponResource::collection($coupons));
    }
}
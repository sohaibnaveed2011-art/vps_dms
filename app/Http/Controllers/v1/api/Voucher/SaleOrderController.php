<?php

namespace App\Http\Controllers\v1\api\Voucher;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Voucher\StoreSaleOrderRequest;
use App\Http\Requests\Voucher\UpdateSaleOrderRequest;
use App\Http\Resources\Voucher\SaleOrderResource;
use App\Services\Voucher\SaleOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleOrderController extends BaseApiController
{
    protected array $permissions = [
        'index' => 'sale.order.view',
        'store' => 'sale.order.create',
        'show' => 'sale.order.show',
        'update' => 'sale.order.update',
        'destroy' => 'sale.order.delete',
        'restore' => 'sale.order.restore',
        'forceDelete' => 'sale.order.forceDelete',
        'submit' => 'sale.order.submit',
        'review' => 'sale.order.review',
        'approve' => 'sale.order.approve',
        'reject' => 'sale.order.reject',
        'confirm' => 'sale.order.confirm',
        'cancel' => 'sale.order.cancel',
    ];

    public function __construct(protected SaleOrderService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of sale orders
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only([
            'search', 'branch_id', 'customer_id', 'voucher_type_id', 
            'status', 'date_from', 'date_to', 'need_review', 'need_approval'
        ]);
        
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            SaleOrderResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    /**
     * Store a newly created sale order
     */
    public function store(StoreSaleOrderRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'sales');

        $saleOrder = $this->service->create($this->getValidatedData($request));

        return $this->created('Sale order created successfully.');
    }

    /**
     * Display the specified sale order
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $saleOrder = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $saleOrder);

        return $this->success(new SaleOrderResource($saleOrder));
    }

    /**
     * Update the specified sale order
     */
    public function update(UpdateSaleOrderRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $saleOrder = $this->service->find($id, $orgId);
        
        $this->authorizeAction($request, $saleOrder);
        
        // Handle workflow actions if present
        $action = $request->input('action');
        
        $result = match($action) {
            'submit' => $this->service->submitForApproval($saleOrder, $request->input('note')),
            'review' => $this->service->review($saleOrder, $request->input('note')),
            'approve' => $this->service->approve($saleOrder, $request->input('note')),
            'reject' => $this->service->reject($saleOrder, $request->input('reason'), null, $request->input('note')),
            'confirm' => $this->service->confirm($saleOrder, $request->input('note')),
            'cancel' => $this->service->cancel($saleOrder, $request->input('reason')),
            default => $this->service->update($saleOrder, $request->validated()),
        };
        
        $message = $this->getWorkflowMessage($action);
        
        return $this->success([new SaleOrderResource($result), $message]);
    }

    /**
     * Remove the specified sale order (soft delete)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $saleOrder = $this->service->find($id, $orgId);
        
        $this->authorizeAction($request, $saleOrder);
        $this->service->delete($saleOrder);

        return $this->deleted('Sale order deleted successfully.');
    }

    /**
     * Restore a soft-deleted sale order
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $saleOrder = $this->service->find($id, $this->getActiveOrgId($request), true);
        
        $this->authorizeAction($request, $saleOrder);
        $this->service->restore($saleOrder);

        return $this->success(['message' => 'Sale order restored successfully.']);
    }

    /**
     * Permanently delete a sale order
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $saleOrder = $this->service->find($id, $this->getActiveOrgId($request), true);
        
        $this->authorizeAction($request, $saleOrder);
        $this->service->forceDelete($saleOrder);

        return $this->deleted('Sale order permanently deleted.');
    }

    /**
     * Submit sale order for approval
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        
        $orgId = $this->getActiveOrgId($request);
        $saleOrder = $this->service->find($id, $orgId);
        $this->authorizeAction($request, $saleOrder);
        
        $result = $this->service->submitForApproval($saleOrder, $request->input('note'));
        
        return $this->success([new SaleOrderResource($result), 'Sale order submitted for approval.']);
    }

    /**
     * Review sale order (first level approval)
     */
    public function review(Request $request, int $id): JsonResponse
    {
        
        $orgId = $this->getActiveOrgId($request);
        $saleOrder = $this->service->find($id, $orgId);
        $this->authorizeAction($request, $saleOrder);
        
        $result = $this->service->review($saleOrder, $request->input('note'));
        
        return $this->success([new SaleOrderResource($result), 'Sale order reviewed successfully.']);
    }

    /**
     * Approve sale order (second level approval)
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        
        $orgId = $this->getActiveOrgId($request);
        $saleOrder = $this->service->find($id, $orgId);
        $this->authorizeAction($request, $saleOrder);
        
        $result = $this->service->approve($saleOrder, $request->input('note'));
        
        return $this->success([new SaleOrderResource($result), 'Sale order approved successfully.']);
    }

    /**
     * Reject sale order
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        
        $request->validate([
            'reason' => 'required|string|max:500',
            'note' => 'nullable|string|max:500',
            ]);
            
            $orgId = $this->getActiveOrgId($request);
            $saleOrder = $this->service->find($id, $orgId);
            $this->authorizeAction($request, $saleOrder);
        
        $result = $this->service->reject($saleOrder, $request->reason, null, $request->note);
        
        return $this->success([new SaleOrderResource($result), 'Sale order rejected.']);
    }

    /**
     * Confirm sale order (after approval)
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        
        $orgId = $this->getActiveOrgId($request);
        $saleOrder = $this->service->find($id, $orgId);
        $this->authorizeAction($request, $saleOrder);
        
        $result = $this->service->confirm($saleOrder, $request->input('note'));
        
        return $this->success([new SaleOrderResource($result), 'Sale order confirmed successfully.']);
    }

    /**
     * Cancel sale order
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        
        $request->validate([
            'reason' => 'required|string|max:500',
            ]);
            
            $orgId = $this->getActiveOrgId($request);
            $saleOrder = $this->service->find($id, $orgId);
            $this->authorizeAction($request, $saleOrder);
        
        $result = $this->service->cancel($saleOrder, $request->reason);
        
        return $this->success([new SaleOrderResource($result), 'Sale order cancelled.']);
    }

    /**
     * Get workflow message based on action
     */
    protected function getWorkflowMessage(?string $action): string
    {
        return match($action) {
            'submit' => 'Sale order submitted for approval.',
            'review' => 'Sale order reviewed successfully.',
            'approve' => 'Sale order approved successfully.',
            'reject' => 'Sale order rejected.',
            'confirm' => 'Sale order confirmed successfully.',
            'cancel' => 'Sale order cancelled.',
            default => 'Sale order updated successfully.',
        };
    }
}
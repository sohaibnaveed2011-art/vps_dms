<?php

namespace App\Http\Controllers\v1\api\Voucher;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Voucher\StoreSaleOrderRequest;
use App\Http\Requests\Voucher\UpdateSaleOrderRequest;
use App\Http\Resources\Voucher\SaleOrderResource;
use App\Services\Voucher\SaleOrderService;
use App\Services\Voucher\VoucherWorkflowService;
use Illuminate\Http\Request;

class SaleOrderController extends BaseApiController
{
    protected array $permissions = [
        'index' => 'vouchers.saleOrder.view',
        'store' => 'vouchers.saleOrder.create',
        'show' => 'vouchers.saleOrder.show',
        'update' => 'vouchers.saleOrder.update',
        'destroy' => 'vouchers.saleOrder.delete',
        'restore' => 'vouchers.saleOrder.restore',
        'forceDelete' => 'vouchers.saleOrder.forceDelete',
        'review' => 'vouchers.saleOrder.review',
        'approve' => 'vouchers.saleOrder.approve',
    ];

    public function __construct(
        protected SaleOrderService $service,
        protected VoucherWorkflowService $workflow
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    /* =========================================================
     | LIST
     ========================================================= */

    public function index(Request $request)
    {
        $this->authorizeAction($request);

        $orders = $this->service->paginate(
            $request->only(['customer_id', 'status', 'date_from', 'date_to']),
            (int) $request->input('per_page', 15),
            $request->user()
        );

        return $this->success(
            SaleOrderResource::collection($orders->items()),
            [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
            ]
        );
    }

    /* =========================================================
     | SHOW
     ========================================================= */

    public function show(Request $request, int $id)
    {
        $order = $this->service->find($id, $request->user())
            ?? abort(404, 'Sale order not found');

        $this->authorizeAction($request, $order);

        return $this->success(new SaleOrderResource($order));
    }

    /* =========================================================
     | CREATE
     ========================================================= */

    public function store(StoreSaleOrderRequest $request)
    {
        $this->authorizeAction($request);

        $order = $this->service->create(
            $request->validated(),
            $request->user()
        );

        return $this->created(new SaleOrderResource($order));
    }

    /* =========================================================
     | UPDATE
     ========================================================= */

    public function update(UpdateSaleOrderRequest $request, int $id)
    {
        $order = $this->service->find($id, $request->user())
            ?? abort(404, 'Sale order not found');

        $this->authorizeAction($request, $order);

        $updated = $this->service->update(
            $order,
            $request->validated(),
            $request->user()
        );

        return $this->success(new SaleOrderResource($updated));
    }

    /* =========================================================
     | DELETE
     ========================================================= */

    public function destroy(Request $request, int $id)
    {
        $order = $this->service->find($id, $request->user())
            ?? abort(404, 'Sale order not found');

        $this->authorizeAction($request, $order);

        $this->service->delete($order, $request->user());

        return $this->deleted('Sale order deleted');
    }

    public function restore(Request $request, int $id)
    {
        $order = $this->service->findWithTrashed($id, $request->user())
            ?? abort(404, 'Sale order not found');

        $this->authorizeAction($request, $order);

        return $this->success(
            new SaleOrderResource(
                $this->service->restore($order, $request->user())
            )
        );
    }

    public function forceDelete(Request $request, int $id)
    {
        $order = $this->service->findWithTrashed($id, $request->user())
            ?? abort(404, 'Sale order not found');

        $this->authorizeAction($request, $order);

        $this->service->forceDelete($order, $request->user());

        return $this->deleted('Sale order permanently deleted');
    }

    /* =========================================================
     | WORKFLOW
     ========================================================= */

    public function review(Request $request, int $id)
    {
        $order = $this->service->find($id, $request->user())
            ?? abort(404, 'Sale order not found');

        $this->workflow->review($request->user(), $order);

        return $this->success(
            new SaleOrderResource($order->refresh())
        );
    }

    public function approve(Request $request, int $id)
    {
        $order = $this->service->find($id, $request->user())
            ?? abort(404, 'Sale order not found');

        $this->workflow->approve($request->user(), $order);

        return $this->success(
            new SaleOrderResource($order->refresh())
        );
    }
}

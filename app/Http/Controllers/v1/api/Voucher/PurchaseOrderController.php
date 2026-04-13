<?php

namespace App\Http\Controllers\v1\api\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\StorePurchaseOrderRequest;
use App\Http\Requests\Voucher\UpdatePurchaseOrderRequest;
use App\Http\Resources\Voucher\PurchaseOrderResource;
use App\Services\Voucher\PurchaseOrderService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    protected PurchaseOrderService $service;

    public function __construct(PurchaseOrderService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['organization_id', 'supplier_id', 'status', 'date_from', 'date_to']);
        $orders = $this->service->list($filters, $request->get('per_page', 15));

        return PurchaseOrderResource::collection($orders);
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $order = $this->service->create($request->validated());

        return new PurchaseOrderResource($order);
    }

    public function show($id)
    {
        $order = $this->service->find($id);
        if (! $order) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new PurchaseOrderResource($order);
    }

    public function update(UpdatePurchaseOrderRequest $request, $id)
    {
        $order = $this->service->update($id, $request->validated());
        if (! $order) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new PurchaseOrderResource($order);
    }

    public function destroy($id)
    {
        if (! $this->service->delete($id)) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json(null, 204);
    }

    public function approve($id)
    {
        $order = $this->service->approve($id);
        if (! $order) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new PurchaseOrderResource($order);
    }

    public function cancel($id)
    {
        $order = $this->service->cancel($id);
        if (! $order) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new PurchaseOrderResource($order);
    }
}

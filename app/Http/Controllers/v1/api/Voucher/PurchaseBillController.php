<?php

namespace App\Http\Controllers\v1\api\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\StorePurchaseBillRequest;
use App\Http\Requests\Voucher\UpdatePurchaseBillRequest;
use App\Http\Resources\Voucher\PurchaseBillResource;
use App\Services\Voucher\PurchaseBillService;
use Illuminate\Http\Request;

class PurchaseBillController extends Controller
{
    protected PurchaseBillService $service;

    public function __construct(PurchaseBillService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['organization_id', 'supplier_id', 'status', 'date_from', 'date_to']);
        $bills = $this->service->list($filters, $request->get('per_page', 15));

        return PurchaseBillResource::collection($bills);
    }

    public function store(StorePurchaseBillRequest $request)
    {
        $bill = $this->service->create($request->validated());

        return new PurchaseBillResource($bill);
    }

    public function show($id)
    {
        $bill = $this->service->find($id);
        if (! $bill) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new PurchaseBillResource($bill);
    }

    public function update(UpdatePurchaseBillRequest $request, $id)
    {
        $bill = $this->service->update($id, $request->validated());
        if (! $bill) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new PurchaseBillResource($bill);
    }

    public function destroy($id)
    {
        if (! $this->service->delete($id)) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json(null, 204);
    }

    public function post($id)
    {
        $bill = $this->service->post($id);
        if (! $bill) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new PurchaseBillResource($bill);
    }
}

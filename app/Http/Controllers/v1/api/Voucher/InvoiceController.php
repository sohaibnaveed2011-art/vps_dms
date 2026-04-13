<?php

namespace App\Http\Controllers\v1\api\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Resources\Voucher\InvoiceResource;
use App\Services\Voucher\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected InvoiceService $service;

    public function __construct(InvoiceService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $invoices = $this->service->list($request->only('q'), $request->get('per_page', 15));

        return InvoiceResource::collection($invoices);
    }

    public function show($id)
    {
        $invoice = $this->service->find($id);
        if (! $invoice) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new InvoiceResource($invoice->load('customer'));
    }

    public function post($id)
    {
        $invoice = $this->service->find($id);
        if (! $invoice) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $invoice = $this->service->post($invoice);

        return new InvoiceResource($invoice->load('customer'));
    }
}

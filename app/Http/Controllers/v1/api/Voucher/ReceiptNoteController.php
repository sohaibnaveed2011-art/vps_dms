<?php

namespace App\Http\Controllers\v1\api\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\StoreReceiptNoteRequest;
use App\Http\Requests\Voucher\UpdateReceiptNoteRequest;
use App\Http\Resources\Voucher\ReceiptNoteResource;
use App\Services\Voucher\ReceiptNoteService;
use Illuminate\Http\Request;

class ReceiptNoteController extends Controller
{
    protected ReceiptNoteService $service;

    public function __construct(ReceiptNoteService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['organization_id', 'purchase_bill_id', 'status', 'date_from', 'date_to']);
        $notes = $this->service->list($filters, $request->get('per_page', 15));

        return ReceiptNoteResource::collection($notes);
    }

    public function store(StoreReceiptNoteRequest $request)
    {
        $note = $this->service->create($request->validated());

        return new ReceiptNoteResource($note);
    }

    public function show($id)
    {
        $note = $this->service->find($id);
        if (! $note) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new ReceiptNoteResource($note);
    }

    public function update(UpdateReceiptNoteRequest $request, $id)
    {
        $note = $this->service->update($id, $request->validated());
        if (! $note) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new ReceiptNoteResource($note);
    }

    public function destroy($id)
    {
        if (! $this->service->delete($id)) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json(null, 204);
    }
}

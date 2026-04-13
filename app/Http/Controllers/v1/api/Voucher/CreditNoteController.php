<?php

namespace App\Http\Controllers\v1\api\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\StoreCreditNoteRequest;
use App\Http\Requests\Voucher\UpdateCreditNoteRequest;
use App\Http\Resources\Voucher\CreditNoteResource;
use App\Services\Voucher\CreditNoteService;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    protected CreditNoteService $service;

    public function __construct(CreditNoteService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['organization_id', 'customer_id', 'date_from', 'date_to']);
        $notes = $this->service->list($filters, $request->get('per_page', 15));

        return CreditNoteResource::collection($notes);
    }

    public function store(StoreCreditNoteRequest $request)
    {
        $note = $this->service->create($request->validated());

        return new CreditNoteResource($note);
    }

    public function show($id)
    {
        $note = $this->service->find($id);
        if (! $note) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new CreditNoteResource($note);
    }

    public function update(UpdateCreditNoteRequest $request, $id)
    {
        $note = $this->service->update($id, $request->validated());
        if (! $note) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new CreditNoteResource($note);
    }

    public function destroy($id)
    {
        if (! $this->service->delete($id)) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json(null, 204);
    }
}

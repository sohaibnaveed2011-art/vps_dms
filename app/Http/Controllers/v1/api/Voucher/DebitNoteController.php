<?php

namespace App\Http\Controllers\v1\api\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\StoreDebitNoteRequest;
use App\Http\Requests\Voucher\UpdateDebitNoteRequest;
use App\Http\Resources\Voucher\DebitNoteResource;
use App\Services\Voucher\DebitNoteService;
use Illuminate\Http\Request;

class DebitNoteController extends Controller
{
    protected DebitNoteService $service;

    public function __construct(DebitNoteService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['organization_id', 'supplier_id', 'date_from', 'date_to']);
        $notes = $this->service->list($filters, $request->get('per_page', 15));

        return DebitNoteResource::collection($notes);
    }

    public function store(StoreDebitNoteRequest $request)
    {
        $note = $this->service->create($request->validated());

        return new DebitNoteResource($note);
    }

    public function show($id)
    {
        $note = $this->service->find($id);
        if (! $note) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new DebitNoteResource($note);
    }

    public function update(UpdateDebitNoteRequest $request, $id)
    {
        $note = $this->service->update($id, $request->validated());
        if (! $note) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new DebitNoteResource($note);
    }

    public function destroy($id)
    {
        if (! $this->service->delete($id)) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json(null, 204);
    }
}

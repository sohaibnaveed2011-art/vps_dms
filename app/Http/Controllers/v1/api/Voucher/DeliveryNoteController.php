<?php

namespace App\Http\Controllers\v1\api\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\StoreDeliveryNoteRequest;
use App\Http\Requests\Voucher\UpdateDeliveryNoteRequest;
use App\Http\Resources\Voucher\DeliveryNoteResource;
use App\Services\Voucher\DeliveryNoteService;
use Illuminate\Http\Request;

class DeliveryNoteController extends Controller
{
    protected DeliveryNoteService $service;

    public function __construct(DeliveryNoteService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['organization_id', 'invoice_id', 'status', 'date_from', 'date_to']);
        $notes = $this->service->list($filters, $request->get('per_page', 15));

        return DeliveryNoteResource::collection($notes);
    }

    public function store(StoreDeliveryNoteRequest $request)
    {
        $note = $this->service->create($request->validated());

        return new DeliveryNoteResource($note);
    }

    public function show($id)
    {
        $note = $this->service->find($id);
        if (! $note) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new DeliveryNoteResource($note);
    }

    public function update(UpdateDeliveryNoteRequest $request, $id)
    {
        $note = $this->service->update($id, $request->validated());
        if (! $note) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return new DeliveryNoteResource($note);
    }

    public function destroy($id)
    {
        if (! $this->service->delete($id)) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json(null, 204);
    }
}

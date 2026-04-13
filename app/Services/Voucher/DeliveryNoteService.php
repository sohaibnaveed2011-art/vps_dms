<?php

namespace App\Services\Voucher;

use App\Models\Voucher\DeliveryNote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryNoteService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DeliveryNote::query();

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['invoice_id'])) {
            $query->where('invoice_id', $filters['invoice_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        return $query->with(['organization', 'saleOrder', 'invoice', 'rider', 'updatedBy', 'items'])->paginate($perPage);
    }

    public function find(int $id): ?DeliveryNote
    {
        return DeliveryNote::with(['organization', 'saleOrder', 'invoice', 'rider', 'updatedBy', 'items'])->find($id);
    }

    public function create(array $data): DeliveryNote
    {
        return DeliveryNote::create($data);
    }

    public function update(int $id, array $data): ?DeliveryNote
    {
        $note = DeliveryNote::find($id);
        if (! $note) {
            return null;
        }

        $note->update($data);

        return $note->fresh();
    }

    public function delete(int $id): bool
    {
        $note = DeliveryNote::find($id);

        return $note ? $note->delete() : false;
    }

    public function post(int $id): ?DeliveryNote
    {
        $note = DeliveryNote::find($id);
        if (! $note) {
            return null;
        }

        $note->update(['status' => 'posted']);

        $fresh = $note->fresh();

        // broadcast delivery note posted for realtime UI
        event(new \App\Events\DeliveryNotePosted([
            'reference_type' => get_class($fresh),
            'reference_id' => $fresh->id,
            'document_type' => 'delivery_note',
            'organization_id' => $fresh->organization_id ?? null,
            'branch_id' => $fresh->branch_id ?? null,
            'created_by' => $fresh->rider ?? ($fresh->created_by ?? null),
            'items_count' => method_exists($fresh, 'items') ? $fresh->items()->count() : null,
        ]));

        return $fresh;
    }
}

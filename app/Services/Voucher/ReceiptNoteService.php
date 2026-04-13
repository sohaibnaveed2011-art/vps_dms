<?php

namespace App\Services\Voucher;

use App\Models\Voucher\ReceiptNote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReceiptNoteService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ReceiptNote::query();

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['purchase_bill_id'])) {
            $query->where('purchase_bill_id', $filters['purchase_bill_id']);
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

        return $query->with(['organization', 'purchaseOrder', 'purchaseBill', 'receivedBy', 'updatedBy', 'items'])->paginate($perPage);
    }

    public function find(int $id): ?ReceiptNote
    {
        return ReceiptNote::with(['organization', 'purchaseOrder', 'purchaseBill', 'receivedBy', 'updatedBy', 'items'])->find($id);
    }

    public function create(array $data): ReceiptNote
    {
        return ReceiptNote::create($data);
    }

    public function update(int $id, array $data): ?ReceiptNote
    {
        $note = ReceiptNote::find($id);
        if (! $note) {
            return null;
        }

        $note->update($data);

        return $note->fresh();
    }

    public function delete(int $id): bool
    {
        $note = ReceiptNote::find($id);

        return $note ? $note->delete() : false;
    }

    public function post(int $id): ?ReceiptNote
    {
        $note = ReceiptNote::find($id);
        if (! $note) {
            return null;
        }

        $note->update(['status' => 'posted']);

        $fresh = $note->fresh();

        // broadcast receipt note posted for realtime UI
        event(new \App\Events\ReceiptNotePosted([
            'reference_type' => get_class($fresh),
            'reference_id' => $fresh->id,
            'document_type' => 'receipt_note',
            'organization_id' => $fresh->organization_id ?? null,
            'branch_id' => $fresh->branch_id ?? null,
            'created_by' => $fresh->received_by ?? ($fresh->created_by ?? null),
            'items_count' => method_exists($fresh, 'items') ? $fresh->items()->count() : null,
        ]));

        return $fresh;
    }
}

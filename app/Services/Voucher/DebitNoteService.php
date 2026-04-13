<?php

namespace App\Services\Voucher;

use App\Models\Voucher\DebitNote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DebitNoteService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DebitNote::query();

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        return $query->with(['organization', 'purchaseBill', 'supplier', 'createdBy', 'items'])->paginate($perPage);
    }

    public function find(int $id): ?DebitNote
    {
        return DebitNote::with(['organization', 'purchaseBill', 'supplier', 'createdBy', 'items'])->find($id);
    }

    public function create(array $data): DebitNote
    {
        return DebitNote::create($data);
    }

    public function update(int $id, array $data): ?DebitNote
    {
        $note = DebitNote::find($id);
        if (! $note) {
            return null;
        }

        $note->update($data);

        return $note->fresh();
    }

    public function delete(int $id): bool
    {
        $note = DebitNote::find($id);

        return $note ? $note->delete() : false;
    }

    public function post(int $id): ?DebitNote
    {
        $note = DebitNote::find($id);
        if (! $note) {
            return null;
        }

        $note->update(['status' => 'posted']);

        $fresh = $note->fresh();

        // broadcast debit note posted for realtime UI
        event(new \App\Events\DebitNotePosted([
            'reference_type' => get_class($fresh),
            'reference_id' => $fresh->id,
            'document_type' => 'debit_note',
            'organization_id' => $fresh->organization_id ?? null,
            'branch_id' => $fresh->branch_id ?? null,
            'created_by' => $fresh->created_by ?? null,
            'items_count' => method_exists($fresh, 'items') ? $fresh->items()->count() : null,
        ]));

        return $fresh;
    }
}

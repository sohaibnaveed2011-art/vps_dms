<?php

namespace App\Services\Voucher;

use App\Models\Voucher\CreditNote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CreditNoteService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CreditNote::query();

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        return $query->with(['organization', 'invoice', 'customer', 'createdBy', 'items'])->paginate($perPage);
    }

    public function find(int $id): ?CreditNote
    {
        return CreditNote::with(['organization', 'invoice', 'customer', 'createdBy', 'items'])->find($id);
    }

    public function create(array $data): CreditNote
    {
        return CreditNote::create($data);
    }

    public function update(int $id, array $data): ?CreditNote
    {
        $note = CreditNote::find($id);
        if (! $note) {
            return null;
        }

        $note->update($data);

        return $note->fresh();
    }

    public function delete(int $id): bool
    {
        $note = CreditNote::find($id);

        return $note ? $note->delete() : false;
    }

    public function post(int $id): ?CreditNote
    {
        $note = CreditNote::find($id);
        if (! $note) {
            return null;
        }

        $note->update(['status' => 'posted']);

        $fresh = $note->fresh();

        // broadcast credit note posted for realtime UI
        event(new \App\Events\CreditNotePosted([
            'reference_type' => get_class($fresh),
            'reference_id' => $fresh->id,
            'document_type' => 'credit_note',
            'organization_id' => $fresh->organization_id ?? null,
            'branch_id' => $fresh->branch_id ?? null,
            'created_by' => $fresh->created_by ?? null,
            'items_count' => method_exists($fresh, 'items') ? $fresh->items()->count() : null,
        ]));

        return $fresh;
    }
}

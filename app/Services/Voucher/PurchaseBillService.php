<?php

namespace App\Services\Voucher;

use App\Models\Voucher\PurchaseBill;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseBillService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseBill::query();

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
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

        return $query->with(['organization', 'branch', 'supplier', 'voucherType', 'createdBy', 'items'])->paginate($perPage);
    }

    public function find(int $id): ?PurchaseBill
    {
        return PurchaseBill::with(['organization', 'branch', 'supplier', 'voucherType', 'createdBy', 'items'])->find($id);
    }

    public function create(array $data): PurchaseBill
    {
        return PurchaseBill::create($data);
    }

    public function update(int $id, array $data): ?PurchaseBill
    {
        $bill = PurchaseBill::find($id);
        if (! $bill) {
            return null;
        }

        $bill->update($data);

        return $bill->fresh();
    }

    public function delete(int $id): bool
    {
        $bill = PurchaseBill::find($id);

        return $bill ? $bill->delete() : false;
    }

    public function post(int $id): ?PurchaseBill
    {
        $bill = PurchaseBill::find($id);
        if (! $bill) {
            return null;
        }

        $bill->update(['status' => 'posted']);

        $fresh = $bill->fresh();

        // broadcast voucher posted event for realtime UI and server listeners
        event(new \App\Events\VoucherPosted([
            'reference_type' => get_class($fresh),
            'reference_id' => $fresh->id,
            'document_type' => 'purchase_bill',
            'organization_id' => $fresh->organization_id ?? null,
            'branch_id' => $fresh->branch_id ?? null,
            'created_by' => $fresh->created_by ?? null,
            'items_count' => method_exists($fresh, 'items') ? $fresh->items()->count() : null,
            'totals' => [
                'net' => $fresh->total ?? $fresh->net_total ?? null,
            ],
        ]));

        return $fresh;
    }
}

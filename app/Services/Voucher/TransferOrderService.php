<?php

namespace App\Services\Voucher;

use App\Models\Inventory\TransferOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransferOrderService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TransferOrder::query();

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['source_location_id'])) {
            $query->where('source_location_id', $filters['source_location_id']);
        }

        if (isset($filters['destination_location_id'])) {
            $query->where('destination_location_id', $filters['destination_location_id']);
        }

        return $query->with(['organization', 'requestedBy', 'approvedBy', 'sourceLocation', 'destinationLocation', 'items'])->paginate($perPage);
    }

    public function find(int $id): ?TransferOrder
    {
        return TransferOrder::with(['organization', 'requestedBy', 'approvedBy', 'sourceLocation', 'destinationLocation', 'items'])->find($id);
    }

    public function create(array $data): TransferOrder
    {
        return TransferOrder::create($data);
    }

    public function update(int $id, array $data): ?TransferOrder
    {
        $order = TransferOrder::find($id);
        if (! $order) {
            return null;
        }

        $order->update($data);

        return $order->fresh();
    }

    public function delete(int $id): bool
    {
        $order = TransferOrder::find($id);

        return $order ? $order->delete() : false;
    }

    public function approve(int $id): ?TransferOrder
    {
        $order = TransferOrder::find($id);
        if (! $order) {
            return null;
        }

        $order->update(['status' => 'approved']);

        $fresh = $order->fresh();

        // broadcast voucher approved for realtime UI
        event(new \App\Events\VoucherApproved([
            'reference_type' => get_class($fresh),
            'reference_id' => $fresh->id,
            'document_type' => 'transfer_order',
            'organization_id' => $fresh->organization_id ?? null,
            'branch_id' => $fresh->branch_id ?? null,
            'created_by' => $fresh->approved_by ?? ($fresh->created_by ?? null),
            'items_count' => method_exists($fresh, 'items') ? $fresh->items()->count() : null,
        ]));

        return $fresh;
    }

    public function cancel(int $id): ?TransferOrder
    {
        $order = TransferOrder::find($id);
        if (! $order) {
            return null;
        }

        $order->update(['status' => 'cancelled']);

        return $order->fresh();
    }
}

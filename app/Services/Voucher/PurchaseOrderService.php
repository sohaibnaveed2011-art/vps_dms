<?php

namespace App\Services\Voucher;

use App\Models\Voucher\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseOrderService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseOrder::query();

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
            $query->whereDate('order_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('order_date', '<=', $filters['date_to']);
        }

        return $query->with(['organization', 'branch', 'supplier', 'voucherType', 'createdBy', 'items'])->paginate($perPage);
    }

    public function find(int $id): ?PurchaseOrder
    {
        return PurchaseOrder::with(['organization', 'branch', 'supplier', 'voucherType', 'createdBy', 'items'])->find($id);
    }

    public function create(array $data): PurchaseOrder
    {
        return PurchaseOrder::create($data);
    }

    public function update(int $id, array $data): ?PurchaseOrder
    {
        $order = PurchaseOrder::find($id);
        if (! $order) {
            return null;
        }

        $order->update($data);

        return $order->fresh();
    }

    public function delete(int $id): bool
    {
        $order = PurchaseOrder::find($id);

        return $order ? $order->delete() : false;
    }

    public function approve(int $id): ?PurchaseOrder
    {
        $order = PurchaseOrder::find($id);
        if (! $order) {
            return null;
        }

        $order->update(['status' => 'approved']);

        return $order->fresh();
    }

    public function cancel(int $id): ?PurchaseOrder
    {
        $order = PurchaseOrder::find($id);
        if (! $order) {
            return null;
        }

        $order->update(['status' => 'cancelled']);

        return $order->fresh();
    }
}

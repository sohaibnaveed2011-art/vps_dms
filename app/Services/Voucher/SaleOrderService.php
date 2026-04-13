<?php

namespace App\Services\Voucher;

use App\Models\Core\FinancialYear;
use App\Models\User;
use App\Models\Voucher\SaleOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleOrderService
{
    /* ===============================
     | QUERY
     =============================== */

    public function paginate(array $filters, int $perPage, User $user): LengthAwarePaginator
    {
        return SaleOrder::visibleTo($user)
            ->when($filters['customer_id'] ?? null,
                fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['status'] ?? null,
                fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id, User $user): ?SaleOrder
    {
        return SaleOrder::visibleTo($user)
            ->with(['items.item', 'customer'])
            ->find($id);
    }

    public function findWithTrashed(int $id, User $user): ?SaleOrder
    {
        return SaleOrder::withTrashed()
            ->visibleTo($user)
            ->find($id);
    }

    /* ===============================
     | CREATE / UPDATE
     =============================== */

    public function create(array $data, User $user): SaleOrder
    {
        return DB::transaction(function () use ($data) {

            $fy = $this->resolveFinancialYear(
                $data['organization_id'],
                $data['order_date']
            );

            $order = SaleOrder::create([
                ...$data,
                'financial_year_id' => $fy->id,
                'status' => 'draft',
            ]);

            $this->syncItems($order, $data['items'] ?? []);

            return $order->refresh();
        });
    }

    public function update(SaleOrder $order, array $data, User $user): SaleOrder
    {
        if (! $order->isEditableBy($user)) {
            throw ValidationException::withMessages([
                'edit' => 'Not allowed to edit this sale order',
            ]);
        }

        return DB::transaction(function () use ($order, $data) {

            $order->fill($data)->save();

            if (isset($data['items'])) {
                $order->items()->delete();
                $this->syncItems($order, $data['items']);
            }

            return $order->refresh();
        });
    }

    /* ===============================
     | DELETE
     =============================== */

    public function delete(SaleOrder $order, User $user): void
    {
        if (! $order->canBeDeletedBy($user)) {
            throw ValidationException::withMessages([
                'delete' => 'Cannot delete sale order',
            ]);
        }

        $order->delete();
    }

    public function restore(SaleOrder $order, User $user): SaleOrder
    {
        $order->restore();

        return $order;
    }

    public function forceDelete(SaleOrder $order, User $user): void
    {
        $order->forceDelete();
    }

    /* ===============================
     | HELPERS
     =============================== */

    protected function syncItems(SaleOrder $order, array $items): void
    {
        foreach ($items as $item) {
            $order->items()->create($item);
        }
    }

    protected function resolveFinancialYear(int $orgId, string $date): FinancialYear
    {
        $fy = FinancialYear::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('is_closed', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if (! $fy) {
            throw ValidationException::withMessages([
                'financial_year' => 'No active financial year',
            ]);
        }

        return $fy;
    }
}

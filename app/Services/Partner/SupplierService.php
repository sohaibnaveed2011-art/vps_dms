<?php

namespace App\Services\Partner;

use App\Exceptions\NotFoundException;
use App\Models\Partner\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function paginate(array $filters, int $perPage):LengthAwarePaginator
    {
        return Supplier::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['partner_category_id']), fn($q) => $q->where('partner_category_id', $filters['partner_category_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%{$filters['search']}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /* =========================================================
     | Retrieval
     ========================================================= */

    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Supplier
    {
        $query = Supplier::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $supplier = $query->find($id);

        if (!$supplier) {
            throw new NotFoundException('Supplier not found.');
        }

        return $supplier;
    }

    /* =========================================================
     | Mutations
     ========================================================= */

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier;
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }

    public function restore(int $id, ?int $orgId = null): void
    {
        $supplier = $this->find($id, $orgId, withTrashed: true);

        if (! $supplier->trashed()) {
            throw new NotFoundException('Supplier is not deleted.');
        }

        $supplier->restore();
    }

    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }
}

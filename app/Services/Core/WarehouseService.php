<?php

namespace App\Services\Core;

use App\Exceptions\NotFoundException;
use App\Models\Core\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WarehouseService
{
    /**
     * Fetch paginated warehouses with filtering and tenant isolation.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        $query = Warehouse::query()->with(['organization', 'branch']);

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Unified find method with Admin bypass and Tenant scoping.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Warehouse
    {
        $query = Warehouse::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        // Apply tenant filter only if an orgId is provided (Non-admins or Contextual Admin)
        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $warehouse = $query->find($id);

        if (!$warehouse) {
            throw new NotFoundException('Warehouse not found.');
        }

        return $warehouse;
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);
        return $warehouse;
    }

    public function delete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId)->delete();
    }

    public function restore(int $id, ?int $orgId = null): Warehouse
    {
        $warehouse = $this->find($id, $orgId, withTrashed: true);

        if (!$warehouse->trashed()) {
            throw new NotFoundException('Warehouse is already active.');
        }

        $warehouse->restore();
        return $warehouse;
    }

    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }
}

<?php

namespace App\Services\Core;

use App\Exceptions\NotFoundException;
use App\Models\Core\Outlet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OutletService
{
    /**
     * Fetch paginated outlets with filtering and tenant isolation.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        $query = Outlet::query()->with(['organization', 'branch', 'warehouse']);

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Unified find method for ID retrieval with optional scoping.
     * Supports Admin bypass if $orgId is null.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Outlet
    {
        $query = Outlet::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        // Logic: Apply filter if orgId is present. Skip for System Admins (null).
        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $outlet = $query->find($id);

        if (!$outlet) {
            throw new NotFoundException('Outlet not found.');
        }

        return $outlet;
    }

    /**
     * Create record.
     */
    public function create(array $data): Outlet
    {
        return Outlet::create($data);
    }

    /**
     * Update record.
     */
    public function update(Outlet $outlet, array $data): Outlet
    {
        $outlet->update($data);
        return $outlet;
    }

    /**
     * Soft delete by ID.
     */
    public function delete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId)->delete();
    }

    /**
     * Restore soft-deleted record.
     */
    public function restore(int $id, ?int $orgId = null): Outlet
    {
        $outlet = $this->find($id, $orgId, withTrashed: true);

        if (!$outlet->trashed()) {
            throw new NotFoundException('Outlet is already active.');
        }

        $outlet->restore();
        return $outlet;
    }

    /**
     * Permanent delete.
     */
    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }
}

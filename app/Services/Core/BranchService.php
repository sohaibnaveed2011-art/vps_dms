<?php

namespace App\Services\Core;

use App\Exceptions\NotFoundException;
use App\Models\Core\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchService
{
    /**
     * Fetch paginated branches with filtering and tenant isolation.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        $query = Branch::query();

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $term = '%' . $filters['search'] . '%';
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Unified find method for ID retrieval with optional scoping.
     * Unified retrieval with Admin bypass.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Branch
    {
        $query = Branch::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /**
         * LOGIC:
         * 1. If $orgId is provided (Regular User or Admin in Org Context),
         * we strictly filter by that ID.
         * 2. If $orgId is null (System Admin), we skip the filter
         * and search the entire table.
         */
        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $branch = $query->find($id);

        if (!$branch) {
            // If an admin provided an OrgId and it failed,
            // they get a 404 for that specific Org context.
            throw new NotFoundException('Branch not found.');
        }

        return $branch;
    }

    /**
     * Create a new record.
     */
    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    /**
     * Update a model instance.
     */
    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);
        return $branch;
    }

    /**
     * Soft delete by ID.
     */
    public function delete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId)->delete();
    }

    /**
     * Restore a soft-deleted record.
     */
    public function restore(int $id, ?int $orgId = null): Branch
    {
        $branch = $this->find($id, $orgId, withTrashed: true);

        if (!$branch->trashed()) {
            throw new NotFoundException('Branch is already active.');
        }

        $branch->restore();
        return $branch;
    }

    /**
     * Hard delete a record.
     */
    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }
}

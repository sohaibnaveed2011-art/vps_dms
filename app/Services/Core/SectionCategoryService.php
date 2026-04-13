<?php

namespace App\Services\Core;

use App\Exceptions\NotFoundException;
use App\Models\Core\SectionCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SectionCategoryService
{
    /**
     * Fetch paginated section categories with filtering and tenant isolation.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        $query = SectionCategory::query();

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $term = '%' . $filters['search'] . '%';
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Unified find method for ID retrieval with optional scoping and bypass for Admins.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): SectionCategory
    {
        $query = SectionCategory::query()->with(['organization', 'warehouseSections']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $category = $query->find($id);

        if (!$category) {
            throw new NotFoundException('Section Category not found.');
        }

        return $category;
    }

    /**
     * Create a new category.
     */
    public function create(array $data): SectionCategory
    {
        return SectionCategory::create($data);
    }

    /**
     * Update an existing category.
     */
    public function update(SectionCategory $category, array $data): SectionCategory
    {
        $category->update($data);
        return $category;
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
    public function restore(int $id, ?int $orgId = null): SectionCategory
    {
        $category = $this->find($id, $orgId, withTrashed: true);

        if (!$category->trashed()) {
            throw new NotFoundException('Section Category is already active.');
        }

        $category->restore();
        return $category;
    }

    /**
     * Permanent delete by ID.
     */
    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }
}

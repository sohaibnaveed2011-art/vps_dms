<?php

namespace App\Services\Partner; 

use App\Exceptions\NotFoundException;
use App\Models\Partner\PartnerCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PartnerCategoryService
{
    /**
     * Paginate with type and organization filtering.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return PartnerCategory::query()
            ->where('type', $filters['type'])
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), fn($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified find method for strict ownership checks.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): PartnerCategory
    {
        $query = PartnerCategory::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $category = $query->find($id);

        if (!$category) {
            throw new NotFoundException('Partner Category not found.');
        }

        return $category;
    }

    public function create(array $data): PartnerCategory
    {
        return PartnerCategory::create($data);
    }

    public function update(PartnerCategory $category, array $data): PartnerCategory
    {
        $category->update($data);
        return $category;
    }

    public function delete(PartnerCategory $category): void
    {
        $category->delete();
    }

    public function restore(int $id, ?int $orgId = null): PartnerCategory
    {
        $category = $this->find($id, $orgId, withTrashed: true);

        if (!$category->trashed()) {
            throw new NotFoundException('Partner Category is not deleted.');
        }

        $category->restore();
        return $category;
    }

    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }
}

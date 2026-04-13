<?php

namespace App\Services\Core;

use App\Exceptions\NotFoundException;
use App\Models\Core\WarehouseSection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WarehouseSectionService
{
    /**
     * Paginate with context-aware filtering.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        $query = WarehouseSection::query()
            ->with(['warehouse', 'parentSection', 'childSections', 'sectionCategory']);

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['parent_section_id'])) {
            $query->where('parent_section_id', $filters['parent_section_id']);
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
     * Unified find method with organization scoping.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): WarehouseSection
    {
        $query = WarehouseSection::query()
            ->with(['warehouse', 'parentSection', 'childSections', 'sectionCategory']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $section = $query->find($id);

        if (!$section) {
            throw new NotFoundException('Warehouse Section not found.');
        }

        return $section;
    }

    public function create(array $data): WarehouseSection
    {
        return WarehouseSection::create($data);
    }

    public function update(WarehouseSection $section, array $data): WarehouseSection
    {
        $section->update($data);
        return $section;
    }

    public function delete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId)->delete();
    }

    public function restore(int $id, ?int $orgId = null): WarehouseSection
    {
        $section = $this->find($id, $orgId, withTrashed: true);

        if (!$section->trashed()) {
            throw new NotFoundException('Warehouse Section is not deleted.');
        }

        $section->restore();
        return $section;
    }

    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }

    /* =========================================================
     | Hierarchy & Helper Methods
     ========================================================= */

    public function getHierarchy(int $warehouseId, ?int $orgId = null): Collection
    {
        return WarehouseSection::where('warehouse_id', $warehouseId)
            ->whereNull('parent_section_id')
            ->when($orgId, fn($q) => $q->where('organization_id', $orgId))
            ->with('childSections')
            ->get();
    }

    public function active(array $filters = []): Collection
    {
        return WarehouseSection::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['warehouse_id']), fn($q) => $q->where('warehouse_id', $filters['warehouse_id']))
            ->where('is_active', true)
            ->get();
    }
}

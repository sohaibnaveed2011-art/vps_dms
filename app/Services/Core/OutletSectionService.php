<?php

namespace App\Services\Core;

use App\Exceptions\NotFoundException;
use App\Models\Core\OutletSection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OutletSectionService
{
    /**
     * Fetch paginated outlet sections with filtering and tenant isolation.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        $query = OutletSection::query()
            ->with(['organization', 'outlet', 
            // 'sectionStocks'
            ]);

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['outlet_id'])) {
            $query->where('outlet_id', $filters['outlet_id']);
        }

        if (isset($filters['is_pos_counter'])) {
            $query->where('is_pos_counter', (bool) $filters['is_pos_counter']);
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
     * Unified find method with scoping and bypass for Admins.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): OutletSection
    {
        $query = OutletSection::query()
            ->with(['organization', 'outlet', 
            // 'sectionStocks'
            ]);

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $section = $query->find($id);

        if (!$section) {
            throw new NotFoundException('Outlet Section not found.');
        }

        return $section;
    }

    public function create(array $data): OutletSection
    {
        return OutletSection::create($data);
    }

    public function update(OutletSection $section, array $data): OutletSection
    {
        $section->update($data);
        return $section;
    }

    public function delete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId)->delete();
    }

    public function restore(int $id, ?int $orgId = null): OutletSection
    {
        $section = $this->find($id, $orgId, withTrashed: true);

        if (!$section->trashed()) {
            throw new NotFoundException('Outlet Section is not deleted.');
        }

        $section->restore();
        return $section;
    }

    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }

    /* =========================================================
     | Extra Helper Methods (Scoped by organization where needed)
     ========================================================= */

    public function getByOutlet(int $outletId, ?int $orgId = null): Collection
    {
        return OutletSection::where('outlet_id', $outletId)
            ->when($orgId, fn($q) => $q->where('organization_id', $orgId))
            ->get();
    }

    public function getPosSections(int $outletId, ?int $orgId = null): Collection
    {
        return OutletSection::where('outlet_id', $outletId)
            ->where('is_pos_counter', true)
            ->when($orgId, fn($q) => $q->where('organization_id', $orgId))
            ->get();
    }
}

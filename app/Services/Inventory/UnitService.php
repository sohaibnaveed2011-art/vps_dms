<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UnitService
{
    /**
     * Paginate units with strict context filtering.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Unit::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%" . trim($filters['search']) . "%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('short_name', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified finder with organization scoping and soft-delete support.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Unit
    {
        $query = Unit::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $unit = $query->find($id);

        if (!$unit) {
            throw new NotFoundException('Unit not found.');
        }

        return $unit;
    }

    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);
        return $unit;
    }

    public function delete(Unit $unit): void
    {
        $unit->delete();
    }

    public function restore(Unit $unit): void
    {
        if (!$unit->trashed()) {
            throw new NotFoundException('Unit is not deleted.');
        }

        $unit->restore();
    }

    public function forceDelete(Unit $unit): void
    {
        $unit->forceDelete();
    }
}

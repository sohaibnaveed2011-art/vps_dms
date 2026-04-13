<?php

namespace App\Services\Inventory\Core;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\InventoryCondition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConditionService
{
    /**
     * Paginate conditions with filtering.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return InventoryCondition::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%" . trim($filters['search']) . "%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified find with strict organization scoping.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): InventoryCondition
    {
        $query = InventoryCondition::query();

        if ($withTrashed) $query->withTrashed();
        if ($orgId) $query->where('organization_id', $orgId);

        return $query->find($id) ?? throw new NotFoundException('Inventory condition not found.');
    }

    public function create(array $data): InventoryCondition
    {
        return InventoryCondition::create($data);
    }

    public function update(InventoryCondition $condition, array $data): void
    {
        $condition->update($data);
    }

    public function delete(InventoryCondition $condition): void
    {
        $condition->delete();
    }

    public function restore(InventoryCondition $condition): void
    {
        if (!$condition->trashed()) {
            throw new \RuntimeException("Condition is not deleted.");
        }
        $condition->restore();
    }

    public function forceDelete(InventoryCondition $condition): void
    {
        $condition->forceDelete();
    }

    /**
     * Helper for internal system lookups by code.
     */
    public function getIdByCode(string $code, int $orgId): int
    {
        return InventoryCondition::where('organization_id', $orgId)
            ->where('code', $code)
            ->value('id') ?? throw new NotFoundException("Condition '{$code}' not found.");
    }
}

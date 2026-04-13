<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\Variation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VariationService
{
    /**
     * Paginate with strict organization filtering.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Variation::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%" . trim($filters['search']) . "%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified find method with organization and trash support.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Variation
    {
        $query = Variation::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $variation = $query->find($id);

        if (!$variation) {
            throw new NotFoundException('Variation not found.');
        }

        return $variation;
    }

    public function create(array $data): Variation
    {
        return Variation::create($data);
    }

    public function update(Variation $variation, array $data): Variation
    {
        $variation->update($data);
        return $variation;
    }

    public function delete(Variation $variation): void
    {
        $variation->delete();
    }

    public function restore(Variation $variation): void
    {
        if (!$variation->trashed()) {
            throw new NotFoundException('Variation is not deleted.');
        }

        $variation->restore();
    }

    public function forceDelete(Variation $variation): void
    {
        $variation->forceDelete();
    }
}

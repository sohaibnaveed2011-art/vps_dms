<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandService
{
    /**
     * Paginate with mandatory organization filtering and search.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        return Brand::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%{$filters['search']}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified find method for strict ownership checks.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Brand
    {
        $query = Brand::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $brand = $query->find($id);

        if (!$brand) {
            throw new NotFoundException('Brand not found.');
        }

        return $brand;
    }

    public function create(array $data): Brand
    {
        return Brand::create($data);
    }

    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);
        return $brand;
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();
    }

    public function restore(Brand $brand): void
    {
        if (!$brand->trashed()) {
            throw new NotFoundException('Brand is not deleted.');
        }

        $brand->restore();
    }

    public function forceDelete(Brand $brand): void
    {
        $brand->forceDelete();
    }
}

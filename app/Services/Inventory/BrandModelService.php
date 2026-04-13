<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\BrandModel;
use App\Models\Inventory\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BrandModelService
{
    /**
     * Paginate results with strict brand and organization filtering.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        return BrandModel::query()
            ->where('brand_id', $filters['brand_id'])
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%" . trim($filters['search']) . "%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('slug', 'like', $term)
                        ->orWhere('series', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified finder with explicit organization and brand scoping.
     */
    public function find(int $id, ?int $orgId = null, ?int $brandId = null, bool $withTrashed = false): BrandModel
    {
        $query = BrandModel::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        if ($brandId !== null) {
            $query->where('brand_id', $brandId);
        }

        $model = $query->find($id);

        if (!$model) {
            throw new NotFoundException('Brand Model not found.');
        }

        return $model;
    }

    /**
     * Handle Bulk Creation within a Transaction.
     */
    public function create(array $modelsData, int $brandId, int $orgId): void
    {
        // Check brand existence and ownership once
        $brandExists = Brand::where('id', $brandId)
            ->where('organization_id', $orgId)
            ->exists();

        if (!$brandExists) {
            throw new NotFoundException('The specified Brand does not exist for your organization.');
        }

        DB::transaction(function () use ($modelsData, $brandId, $orgId) {
            foreach ($modelsData as $item) {
                // Eloquent 'create' is used to trigger Model events (like our slug generation)
                BrandModel::create(array_merge($item, [
                    'brand_id' => $brandId,
                    'organization_id' => $orgId
                ]));
            }
        });
    }

    /**
     * Update a single model.
     */
    public function update(BrandModel $model, array $data): BrandModel
    {
        $model->update($data);
        return $model;
    }

    /**
     * Soft delete a model.
     */
    public function delete(BrandModel $model): void
    {
        $model->delete();
    }

    /**
     * Restore a soft-deleted model.
     */
    public function restore(BrandModel $model): void
    {
        if (!$model->trashed()) {
            throw new NotFoundException('Brand Model is not deleted.');
        }

        $model->restore();
    }

    /**
     * Permanently delete a model.
     */
    public function forceDelete(BrandModel $model): void
    {
        $model->forceDelete();
    }
}
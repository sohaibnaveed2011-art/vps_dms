<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\Variation;
use App\Models\Inventory\VariationValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VariationValueService
{
    /**
     * Paginate values with strict variation and organization scoping.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        return VariationValue::query()
            ->where('variation_id', $filters['variation_id'])
            ->when(isset($filters['organization_id']),
                fn($q) => $q->where('organization_id', $filters['organization_id'])
            )
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%" . trim($filters['search']) . "%";
                $q->where('value', 'like', $term);
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified finder ensuring value belongs to both the variation and the organization.
     */
    public function find(int $id, ?int $orgId = null, ?int $variationId = null, bool $withTrashed = false): VariationValue
    {
        $query = VariationValue::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        if ($variationId !== null) {
            $query->where('variation_id', $variationId);
        }

        $value = $query->find($id);

        if (!$value) {
            throw new NotFoundException('Variation value not found.');
        }

        return $value;
    }

    public function create(array $valuesData, int $variationId, int $orgId): void
    {
        // Check variation existence and ownership once
        $variationExists = Variation::whereId($variationId)
            ->where('organization_id', $orgId)
            ->exists();

        if (!$variationExists) {
            throw new NotFoundException('The specified variation does not exist for your organization.');
        }

        DB::transaction(function () use ($valuesData, $variationId, $orgId) {
            foreach ($valuesData as $item) {
                // Eloquent 'create' is used to trigger Model events (like our slug generation)
                VariationValue::create(array_merge($item, [
                    'variation_id' => $variationId,
                    'organization_id' => $orgId
                ]));
            }
        });
    }

    public function update(VariationValue $value, array $data): VariationValue
    {
        $value->update($data);
        return $value;
    }

    public function delete(VariationValue $value): void
    {
        $value->delete();
    }

    public function restore(VariationValue $value): void
    {
        if (!$value->trashed()) {
            throw new NotFoundException('Variation value is not deleted.');
        }

        $value->restore();
    }

    public function forceDelete(VariationValue $value): void
    {
        $value->forceDelete();
    }
}

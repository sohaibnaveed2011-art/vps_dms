<?php

namespace App\Services\Core;

use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Models\Core\FinancialYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinancialYearService
{
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        return FinancialYear::query()
            // Restriction applied here via organization_id filter
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * The $orgId parameter is critical here.
     * If NULL (System Admin), the query is unscoped.
     * If INT (Tenant), the query is strictly restricted to that org.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): FinancialYear
    {
        $query = FinancialYear::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $fy = $query->find($id);

        if (!$fy) {
            throw new NotFoundException('Financial Year not found.');
        }

        return $fy;
    }

    public function create(array $data): FinancialYear
    {
        return FinancialYear::create($data);
    }

    public function update(FinancialYear $fy, array $data): FinancialYear
    {
        $fy->update($data);
        return $fy;
    }

    public function delete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId)->delete();
    }

    public function restore(int $id, ?int $orgId = null): FinancialYear
    {
        $fy = $this->find($id, $orgId, withTrashed: true);

        if (!$fy->trashed()) {
            throw new ConflictException('Financial Year is already active.');
        }

        $fy->restore();
        return $fy;
    }

    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }
}

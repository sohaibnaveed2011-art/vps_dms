<?php

namespace App\Services\Core;

use App\Exceptions\NotFoundException;
use App\Models\Core\Tax;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TaxService
{
    /**
     * Paginate with mandatory organization filtering.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        return Tax::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), fn($q) => $q->where('name', 'like', '%' . $filters['search'] . '%'))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified find method with explicit organization scoping.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Tax
    {
        $query = Tax::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $tax = $query->find($id);

        if (!$tax) {
            throw new NotFoundException('Tax not found.');
        }

        return $tax;
    }

    public function create(array $data): Tax
    {
        return Tax::create($data);
    }

    public function update(Tax $tax, array $data): Tax
    {
        $tax->update($data);
        return $tax;
    }

    public function delete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId)->delete();
    }

    public function restore(int $id, ?int $orgId = null): Tax
    {
        $tax = $this->find($id, $orgId, withTrashed: true);

        if (!$tax->trashed()) {
            throw new NotFoundException('Tax is not deleted.');
        }

        $tax->restore();
        return $tax;
    }

    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }

    public function getActiveByOrganization(int $organizationId): Collection
    {
        return Tax::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->get();
    }
}

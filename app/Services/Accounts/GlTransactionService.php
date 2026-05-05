<?php

namespace App\Services\Account;

use App\Models\Account\GlTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GlTransactionService
{
    /**
     * Retrieve a paginated list of GL transactions.
     * Used for auditing and report generation.
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = GlTransaction::query()
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc');

        $this->applyFilters($query, $filters);

        return $query->paginate(50);
    }

    /**
     * Find a single GL transaction by ID.
     */
    public function find(int $id): ?GlTransaction
    {
        return GlTransaction::find($id);
    }

    /**
     * Helper to apply common search filters to the GL query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['organization_id'])) {
            // GL must be scoped by Organization. Since GlTransaction migration doesn't have an explicit org_id,
            // we must rely on joins through Account (BEST PRACTICE: Add organization_id to GL in migration).
            // Assuming Account relationship works:
            $query->whereHas('account', function ($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        if (isset($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['document_number'])) {
            $query->where('document_number', 'like', '%' . $filters['document_number'] . '%');
        }
    }
}

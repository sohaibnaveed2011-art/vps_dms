<?php

namespace App\Services\Account;

use App\Models\Account\Account;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountService
{
    /**
     * Retrieve a paginated list of accounts.
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Account::query();

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate(15);
    }

    /**
     * Create a new account.
     */
    public function create(array $data): Account
    {
        return Account::create($data);
    }

    /**
     * Find an account by ID.
     */
    public function find(int $id): ?Account
    {
        return Account::find($id);
    }

    /**
     * Update an existing account.
     */
    public function update(int $id, array $data): ?Account
    {
        $account = $this->find($id);
        if ($account) {
            $account->update($data);
        }
        return $account;
    }

    /**
     * Delete an account.
     * Note: Deletion should be restricted if GL transactions exist (handled by DB constraint).
     */
    public function delete(int $id): bool
    {
        // DB constraints will prevent deletion if GL transactions exist.
        return Account::destroy($id) > 0;
    }
}

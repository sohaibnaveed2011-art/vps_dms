<?php

namespace App\Services\Account;

use App\Models\Account\Account;

/**
 * Helper to fetch GL accounts for posting entries.
 * Can be extended to read from config or database settings.
 */
class AccountMappingService
{
    /**
     * Get sales revenue account for organization
     */
    public function getSalesAccount(?int $organizationId = null): ?Account
    {
        return Account::where('type', 'Revenue')
            ->where(function ($q) use ($organizationId) {
                if ($organizationId) {
                    $q->where('organization_id', $organizationId);
                }
            })
            ->first();
    }

    /**
     * Get accounts receivable account for organization
     */
    public function getReceivableAccount(?int $organizationId = null): ?Account
    {
        return Account::where('type', 'Asset')
            ->where(function ($q) use ($organizationId) {
                if ($organizationId) {
                    $q->where('organization_id', $organizationId);
                }
            })
            ->first();
    }

    /**
     * Get COGS (Cost of Goods Sold) expense account
     */
    public function getCogsAccount(?int $organizationId = null): ?Account
    {
        return Account::where('type', 'Expense')
            ->where('name', 'like', '%COGS%')
            ->where(function ($q) use ($organizationId) {
                if ($organizationId) {
                    $q->where('organization_id', $organizationId);
                }
            })
            ->first();
    }

    /**
     * Get inventory asset account
     */
    public function getInventoryAccount(?int $organizationId = null): ?Account
    {
        return Account::where('type', 'Asset')
            ->where('name', 'like', '%Inventory%')
            ->where(function ($q) use ($organizationId) {
                if ($organizationId) {
                    $q->where('organization_id', $organizationId);
                }
            })
            ->first();
    }
}

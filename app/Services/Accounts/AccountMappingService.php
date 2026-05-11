<?php

namespace App\Services\Accounts;

use App\Models\Accounts\Account;
use Illuminate\Support\Facades\Cache;

class AccountMappingService
{
    /**
     * Get sales revenue account for organization
     */
    public function getSalesAccount(int $organizationId): ?Account
    {
        $cacheKey = "org_{$organizationId}_sales_account";
        
        return Cache::remember($cacheKey, 3600, function () use ($organizationId) {
            return Account::where('organization_id', $organizationId)
                ->where('type', 'Revenue')
                ->where('is_active', true)
                ->where('is_group', false)
                ->first();
        });
    }

    /**
     * Get accounts receivable account
     */
    public function getReceivableAccount(int $organizationId): ?Account
    {
        return Cache::remember("org_{$organizationId}_receivable_account", 3600, function () use ($organizationId) {
            return Account::where('organization_id', $organizationId)
                ->where('type', 'Asset')
                ->where(function ($q) {
                    $q->where('name', 'like', '%Receivable%')
                      ->orWhere('code', 'like', '12%');
                })
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get COGS account
     */
    public function getCogsAccount(int $organizationId): ?Account
    {
        return Cache::remember("org_{$organizationId}_cogs_account", 3600, function () use ($organizationId) {
            return Account::where('organization_id', $organizationId)
                ->where('type', 'Expense')
                ->where(function ($q) {
                    $q->where('name', 'like', '%COGS%')
                      ->orWhere('name', 'like', '%Cost of Goods%');
                })
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get inventory account
     */
    public function getInventoryAccount(int $organizationId): ?Account
    {
        return Cache::remember("org_{$organizationId}_inventory_account", 3600, function () use ($organizationId) {
            return Account::where('organization_id', $organizationId)
                ->where('type', 'Asset')
                ->where(function ($q) {
                    $q->where('name', 'like', '%Inventory%')
                      ->orWhere('code', 'like', '13%');
                })
                ->where('is_active', true)
                ->first();
        });
    }
    
    /**
     * Get retained earnings account
     */
    public function getRetainedEarningsAccount(int $organizationId): ?Account
    {
        return Cache::remember("org_{$organizationId}_retained_earnings", 3600, function () use ($organizationId) {
            return Account::where('organization_id', $organizationId)
                ->where('type', 'Equity')
                ->where(function ($q) {
                    $q->where('name', 'like', '%Retained Earnings%')
                      ->orWhere('code', 'like', '32%');
                })
                ->first();
        });
    }
}
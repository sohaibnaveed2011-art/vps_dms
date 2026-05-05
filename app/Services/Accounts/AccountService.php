<?php

namespace App\Services\Accounts;

use App\Models\Accounts\Account;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AccountService
{
    protected array $relations = [
        'parent',
        'children',
    ];

    public function __construct()
    {}

    /**
     * Paginate accounts with filters
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        return Account::query()
            ->with($this->relations)
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['parent_id']), fn($q) => $q->where('parent_id', $filters['parent_id']))
            ->when(isset($filters['is_group']), fn($q) => $q->where('is_group', $filters['is_group']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['is_taxable']), fn($q) => $q->where('is_taxable', $filters['is_taxable']))
            ->when(isset($filters['currency_code']), fn($q) => $q->where('currency_code', $filters['currency_code']))
            ->when(isset($filters['min_level']), fn($q) => $q->where('level', '>=', $filters['min_level']))
            ->when(isset($filters['max_level']), fn($q) => $q->where('level', '<=', $filters['max_level']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%" . trim($filters['search']) . "%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->orderBy('code')
            ->paginate($perPage);
    }

    /**
     * Find account by ID
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Account
    {
        $query = Account::query();
        $query->with($this->relations);

        if ($withTrashed) {
            $query->withTrashed();
        }
        
        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $account = $query->find($id);
        
        if (!$account) {
            throw new NotFoundException('Account not found.');
        }
        
        return $account;
    }

    /**
     * Create a new account
     */
    public function create(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            // Auto-calculate level if not provided
            if (empty($data['level']) && !empty($data['parent_id'])) {
                $parent = Account::find($data['parent_id']);
                $data['level'] = $parent ? $parent->level + 1 : 0;
            }

            // Set default normal balance based on type if not provided and not a group account
            if (empty($data['normal_balance']) && empty($data['is_group'])) {
                $data['normal_balance'] = $this->getDefaultNormalBalance($data['type']);
            }

            // Set default currency if not provided
            if (empty($data['currency_code'])) {
                $data['currency_code'] = 'PKR';
            }

            // Set default values for flags
            $data['is_group'] = $data['is_group'] ?? false;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_taxable'] = $data['is_taxable'] ?? false;
            $data['automatic_postings_disabled'] = $data['automatic_postings_disabled'] ?? false;

            $account = Account::create($data);

            // Clear organization account cache
            $this->clearAccountCache($account->organization_id);

            return $account->load($this->relations);
        });
    }

    /**
     * Update an existing account
     */
    public function update(Account $account, array $data): Account
    {
        return DB::transaction(function () use ($account, $data) {
            // Check if account has transactions before allowing certain updates
            $hasTransactions = $account->journalLines()->exists();
            
            if ($hasTransactions) {
                // Prevent changes to immutable fields
                $immutableFields = ['type', 'code'];
                foreach ($immutableFields as $field) {
                    if (isset($data[$field]) && $data[$field] != $account->$field) {
                        throw ValidationException::withMessages([
                            $field => "Cannot change {$field} after transactions exist."
                        ]);
                    }
                }
                
                // Prevent parent change if has transactions
                if (isset($data['parent_id']) && $data['parent_id'] != $account->parent_id) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Cannot change parent account after transactions have been posted.'
                    ]);
                }
            }

            // Update level if parent changed
            if (isset($data['parent_id']) && $data['parent_id'] != $account->parent_id) {
                $parent = $data['parent_id'] ? Account::find($data['parent_id']) : null;
                $newLevel = $parent ? $parent->level + 1 : 0;
                $levelDifference = $newLevel - $account->level;
                
                $data['level'] = $newLevel;
                
                // Update all descendants levels recursively
                if ($levelDifference != 0) {
                    $this->updateDescendantsLevels($account, $levelDifference);
                }
            }

            $account->update($data);

            // Clear caches
            $this->clearAccountCache($account->organization_id);
            $this->clearAccountBalanceCache($account->id);

            return $account->load($this->relations);
        });
    }

    /**
     * Delete (soft delete) an account
     */
    public function delete(Account $account): void
    {
        // Check if account can be deleted
        if (!$this->isDeletable($account)) {
            throw ValidationException::withMessages([
                'account' => 'Cannot delete account with existing transactions or child accounts.'
            ]);
        }
        
        $account->delete();
        $this->clearAccountCache($account->organization_id);
    }

    /**
     * Restore a soft-deleted account
     */
    public function restore(Account $account): void
    {
        $account->restore();
        $this->clearAccountCache($account->organization_id);
    }

    /**
     * Permanently delete an account
     */
    public function forceDelete(Account $account): void
    {
        // Check if account has transactions
        if ($account->journalLines()->exists()) {
            throw ValidationException::withMessages([
                'account' => 'Cannot permanently delete account with existing transactions.'
            ]);
        }
        
        // Check if account has children
        if ($account->children()->exists()) {
            throw ValidationException::withMessages([
                'account' => 'Cannot permanently delete account with child accounts.'
            ]);
        }
        
        $account->forceDelete();
        $this->clearAccountCache($account->organization_id);
    }

    /**
     * Toggle account status
     */
    public function toggleStatus(Account $account, bool $isActive): Account
    {
        // Check if trying to deactivate account with balance
        if (!$isActive && $account->getCurrentBalanceAttribute() != 0) {
            throw ValidationException::withMessages([
                'is_active' => 'Cannot deactivate account with non-zero balance.'
            ]);
        }
        
        $account->update(['is_active' => $isActive]);
        $this->clearAccountCache($account->organization_id);
        
        return $account;
    }

    /**
     * Get account tree structure
     */
    public function getTree(int $organizationId, array $options = []): array
    {
        $cacheKey = "account_tree_{$organizationId}_" . md5(json_encode($options));
        
        return Cache::remember($cacheKey, 3600, function () use ($organizationId, $options) {
            $query = Account::where('organization_id', $organizationId);
            
            // Filter by active status
            if (!($options['include_inactive'] ?? false)) {
                $query->where('is_active', true);
            }
            
            // Filter by account types
            if (!empty($options['types'])) {
                $query->whereIn('type', (array) $options['types']);
            }
            
            // Get root accounts
            $rootAccounts = $query->whereNull('parent_id')
                ->orderBy('code')
                ->get();
            
            // Build tree with depth limit
            $maxDepth = $options['max_depth'] ?? null;
            return $this->buildTree($rootAccounts, $maxDepth);
        });
    }

    /**
     * Get account types with counts
     */
    public function getAccountTypesWithCounts(int $organizationId): array
    {
        $cacheKey = "account_types_{$organizationId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($organizationId) {
            $types = ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'];
            $result = [];
            
            foreach ($types as $type) {
                $count = Account::where('organization_id', $organizationId)
                    ->where('type', $type)
                    ->count();
                
                $result[] = [
                    'type' => $type,
                    'count' => $count,
                    'label' => $this->getTypeLabel($type),
                ];
            }
            
            return $result;
        });
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(Account $account, ?string $asOfDate = null): float
    {
        $date = $asOfDate ?? date('Y-m-d');
        $cacheKey = "account_balance_{$account->id}_{$date}";
        
        return Cache::remember($cacheKey, 300, function () use ($account, $date) {
            return $account->getBalanceAsOf($date);
        });
    }

    /**
     * Get trial balance
     */
    public function getTrialBalance(int $organizationId, array $params = []): array
    {
        $asOfDate = $params['as_of_date'] ?? date('Y-m-d');
        $fromDate = $params['from_date'] ?? null;
        $toDate = $params['to_date'] ?? $asOfDate;
        
        // Ensure we're getting Account models, not stdClass
        $accounts = Account::where('organization_id', $organizationId)
            ->where('is_group', false)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        
        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($accounts as $account) {
            // Make sure $account is an Account instance
            if (!$account instanceof Account) {
                continue;
            }
            
            // Get balance for the period
            if ($fromDate) {
                $endingBalance = $account->getBalanceAsOf($toDate);
                $beginningBalance = $account->getBalanceAsOf($fromDate);
                $balance = $endingBalance - $beginningBalance;
            } else {
                $balance = $account->getBalanceAsOf($toDate);
            }
            
            // Determine debit/credit based on normal balance
            if ($account->normal_balance === 'Debit') {
                $debit = $balance > 0 ? $balance : 0;
                $credit = 0;
                $totalDebit += $debit;
            } else {
                $debit = 0;
                $credit = $balance > 0 ? $balance : 0;
                $totalCredit += $credit;
            }
            
            // Skip zero balance if requested
            if (!($params['include_zero_balance'] ?? false) && $debit == 0 && $credit == 0) {
                continue;
            }
            
            $trialBalance[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->type,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'balance' => round($balance, 2),
            ];
        }
        
        return [
            'accounts' => $trialBalance,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'as_of_date' => $toDate,
            'from_date' => $fromDate,
            'account_count' => count($trialBalance),
        ];
    }

    /**
     * Get chart of accounts
     */
    public function getChartOfAccounts(int $organizationId, array $filters = []): array
    {
        $accounts = Account::where('organization_id', $organizationId)
            ->orderBy('code')
            ->get();
        
        $includeBalances = $filters['include_balances'] ?? false;
        $asOfDate = $filters['as_of_date'] ?? date('Y-m-d');
        
        $chart = [];
        foreach ($accounts->whereNull('parent_id') as $rootAccount) {
            if ($rootAccount instanceof Account) {
                $chart[] = $this->buildChartNode($rootAccount, $accounts, $includeBalances, $asOfDate);
            }
        }
        
        return $chart;
    }

    /**
     * Get accounts for select dropdown
     */
    public function getSelectList(int $organizationId, array $filters = []): array
    {
        $query = Account::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('code');
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['is_group'])) {
            $query->where('is_group', $filters['is_group']);
        }
        
        if (!empty($filters['search'])) {
            $term = "%" . trim($filters['search']) . "%";
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }
        
        return $query->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'text' => "{$account->code} - {$account->name}",
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'level' => $account->level,
                'is_group' => $account->is_group,
            ])
            ->toArray();
    }

    /**
     * Get account hierarchy/path
     */
    public function getAccountHierarchy(Account $account): array
    {
        $hierarchy = [];
        $current = $account;
        
        while ($current) {
            array_unshift($hierarchy, [
                'id' => $current->id,
                'code' => $current->code,
                'name' => $current->name,
                'level' => $current->level,
            ]);
            $current = $current->parent;
        }
        
        return $hierarchy;
    }

    /**
     * Get account summary with journal entries
     */
    public function getAccountSummary(Account $account, string $fromDate, string $toDate): array
    {
        $journalLines = $account->journalLines()
            ->with(['journal'])
            ->whereHas('journal', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('date', [$fromDate, $toDate])
                    ->where('is_posted', true)
                    ->where('is_reversed', false);
            })
            ->orderBy('created_at')
            ->get();
        
        $openingBalance = $account->getBalanceAsOf($fromDate);
        $closingBalance = $account->getBalanceAsOf($toDate);
        
        $transactions = $journalLines->map(function ($line) {
            return [
                'date' => $line->journal->date->format('Y-m-d'),
                'voucher_no' => $line->journal->voucher_no,
                'reference_type' => $line->journal->reference_type,
                'reference_id' => $line->journal->reference_id,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'memo' => $line->line_memo ?? $line->journal->memo,
            ];
        });
        
        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');
        
        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'normal_balance' => $account->normal_balance,
            ],
            'period' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'opening_balance' => round($openingBalance, 2),
            'closing_balance' => round($closingBalance, 2),
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'transactions' => $transactions,
            'transaction_count' => $transactions->count(),
        ];
    }

    /**
     * Bulk update multiple accounts
     */
    public function bulkUpdate(array $accounts, int $organizationId): array
    {
        $updated = 0;
        $errors = [];
        
        foreach ($accounts as $accountData) {
            try {
                $account = $this->find($accountData['id'], $organizationId);
                
                $updateData = array_intersect_key($accountData, [
                    'is_active' => true,
                    'is_taxable' => true,
                    'description' => true,
                ]);
                
                if (!empty($updateData)) {
                    $this->update($account, $updateData);
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $accountData['id'],
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return [
            'updated' => $updated,
            'errors' => $errors,
            'total' => count($accounts),
        ];
    }

    /**
     * Export accounts
     */
    public function export(int $organizationId, string $format, array $filters = []): string
    {
        // Build export data
        $accounts = Account::where('organization_id', $organizationId)
            ->when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->orderBy('code')
            ->get();
        
        // Generate export file (CSV, Excel, etc.)
        $fileName = "accounts_export_" . date('Ymd_His') . ".{$format}";
        $filePath = storage_path("app/exports/{$fileName}");
        
        // Ensure directory exists
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        // Write CSV
        if ($format === 'csv') {
            $handle = fopen($filePath, 'w');
            fputcsv($handle, ['Code', 'Name', 'Type', 'Normal Balance', 'Is Active', 'Is Group', 'Description']);
            
            foreach ($accounts as $account) {
                fputcsv($handle, [
                    $account->code,
                    $account->name,
                    $account->type,
                    $account->normal_balance,
                    $account->is_active ? 'Yes' : 'No',
                    $account->is_group ? 'Yes' : 'No',
                    $account->description,
                ]);
            }
            
            fclose($handle);
        }
        
        return $filePath;
    }

    /**
     * Import accounts from file
     *
     * @param UploadedFile $file
     * @param int $organizationId
     * @param bool $overwrite
     * @return array
     */
    public function import(UploadedFile $file, int $organizationId, bool $overwrite = false): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        // Read CSV file
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            
            try {
                $existingAccount = Account::where('organization_id', $organizationId)
                    ->where('code', $data['Code'])
                    ->first();
                
                if ($existingAccount && !$overwrite) {
                    $skipped++;
                    continue;
                }
                
                $accountData = [
                    'organization_id' => $organizationId,
                    'code' => $data['Code'],
                    'name' => $data['Name'],
                    'type' => $data['Type'],
                    'normal_balance' => $data['Normal Balance'] ?? null,
                    'is_active' => ($data['Is Active'] ?? 'Yes') === 'Yes',
                    'is_group' => ($data['Is Group'] ?? 'No') === 'Yes',
                    'description' => $data['Description'] ?? null,
                ];
                
                if ($existingAccount && $overwrite) {
                    $this->update($existingAccount, $accountData);
                } else {
                    $this->create($accountData);
                }
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = [
                    'code' => $data['Code'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        fclose($handle);
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Check if account can be deleted
     */
    protected function isDeletable(Account $account): bool
    {
        return $account->journalLines()->count() === 0 && $account->children()->count() === 0;
    }

    /**
     * Get default normal balance based on account type
     */
    protected function getDefaultNormalBalance(string $type): string
    {
        return match($type) {
            'Asset', 'Expense' => 'Debit',
            'Liability', 'Equity', 'Revenue' => 'Credit',
            default => 'Debit',
        };
    }

    /**
     * Get human-readable type label
     */
    protected function getTypeLabel(string $type): string
    {
        return match($type) {
            'Asset' => 'Assets',
            'Liability' => 'Liabilities',
            'Equity' => 'Equity',
            'Revenue' => 'Revenue',
            'Expense' => 'Expenses',
            default => $type,
        };
    }

    /**
     * Build tree structure recursively
     *
     * @param Collection $accounts
     * @param int|null $maxDepth
     * @param int $currentDepth
     * @return array
     */
    protected function buildTree(Collection $accounts, ?int $maxDepth = null, int $currentDepth = 0): array
    {
        if ($maxDepth !== null && $currentDepth >= $maxDepth) {
            return [];
        }
        
        return $accounts->map(function (Account $account) use ($maxDepth, $currentDepth) {
            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'is_group' => $account->is_group,
                'is_active' => $account->is_active,
                'level' => $account->level,
                'children' => $this->buildTree($account->children, $maxDepth, $currentDepth + 1),
            ];
        })->toArray();
    }

    /**
     * Build chart node recursively
     *
     * @param Account $account
     * @param Collection $allAccounts
     * @param bool $includeBalances
     * @param string $asOfDate
     * @return array
     */
    protected function buildChartNode(Account $account, Collection $allAccounts, bool $includeBalances, string $asOfDate): array
    {
        $node = [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
            'is_group' => $account->is_group,
            'level' => $account->level,
        ];
        
        if ($includeBalances && !$account->is_group) {
            $node['balance'] = $this->getAccountBalance($account, $asOfDate);
        }
        
        $children = $allAccounts->where('parent_id', $account->id);
        if ($children->isNotEmpty()) {
            $node['children'] = $children->map(function (Account $child) use ($allAccounts, $includeBalances, $asOfDate) {
                return $this->buildChartNode($child, $allAccounts, $includeBalances, $asOfDate);
            })->values()->toArray();
        }
        
        return $node;
    }

    /**
     * Update descendant levels recursively
     */
    protected function updateDescendantsLevels(Account $account, int $levelDifference): void
    {
        foreach ($account->children as $child) {
            $child->level += $levelDifference;
            $child->save();
            $this->updateDescendantsLevels($child, $levelDifference);
        }
    }

    /**
     * Clear account cache
     */
    protected function clearAccountCache(int $organizationId): void
    {
        Cache::forget("account_tree_{$organizationId}");
        Cache::forget("account_types_{$organizationId}");
        Cache::forget("chart_of_accounts_{$organizationId}");
    }

    /**
     * Clear account balance cache
     */
    protected function clearAccountBalanceCache(int $accountId): void
    {
        // Clear balance cache for common date patterns
        $dates = ['current', date('Y-m-d')];
        foreach ($dates as $date) {
            Cache::forget("account_balance_{$accountId}_{$date}");
        }
    }
}
<?php

namespace App\Services\Core;

use Carbon\Carbon;
use InvalidArgumentException;
use App\Models\Accounts\Account;
use App\Models\Core\FinancialYear;
use Illuminate\Support\Facades\DB;
use App\Models\Voucher\VoucherType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\Accounts\JournalEntryService;

class FinancialYearClosingService
{
    public function __construct(
        protected JournalEntryService $journalService
    ) {}

    /**
     * Close a financial year and prepare opening balances for next year
     * 
     * @throws InvalidArgumentException
     */
    public function closeYear(FinancialYear $year, array $data): FinancialYear
    {
        if (!$year->canBeClosed()) {
            throw new InvalidArgumentException('Financial year cannot be closed. It may already be closed or not active.');
        }

        DB::beginTransaction();
        
        try {
            // 1. Verify all journals are posted
            $this->verifyAllJournalsPosted($year);
            
            // 2. Calculate closing balances for all accounts
            $closingBalances = $this->calculateClosingBalances($year);
            
            // 3. Create closing journal entries (transfer to retained earnings)
            $closingJournal = $this->createClosingEntries($year, $closingBalances);
            
            // 4. Create next financial year if not exists
            $nextYear = $this->createNextYear($year, $data);
            
            // 5. Create opening balance journal for next year
            if ($nextYear) {
                $this->createOpeningBalanceJournal($nextYear, $closingBalances);
            }
            
            // Get current user ID safely
            $userId = Auth::id();
            
            // 6. Mark current year as closed
            $year->update([
                'is_closed' => true,
                'status' => 'closed',
                'closed_by' => $userId,
                'closed_at' => now(),
                'closure_notes' => $data['closure_notes'] ?? null,
                'closure_summary' => [
                    'closing_balances' => $closingBalances,
                    'closing_journal_id' => $closingJournal ? $closingJournal->id : null,
                    'total_assets' => $closingBalances['total_assets'],
                    'total_liabilities' => $closingBalances['total_liabilities'],
                    'total_equity' => $closingBalances['total_equity'],
                    'net_profit_loss' => $closingBalances['net_profit_loss'],
                ],
            ]);
            
            DB::commit();
            
            Log::info("Financial year {$year->name} closed successfully", [
                'organization_id' => $year->organization_id,
                'closed_by' => $userId,
                'next_year' => $nextYear ? $nextYear->name : null,
            ]);
            
            return $year;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to close financial year {$year->name}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Verify all journals in the period are posted
     * 
     * @throws InvalidArgumentException
     */
    private function verifyAllJournalsPosted(FinancialYear $year): void
    {
        $unpostedCount = $year->journals()
            ->where('is_posted', false)
            ->where('is_reversed', false)
            ->count();
            
        if ($unpostedCount > 0) {
            throw new InvalidArgumentException(
                "Cannot close financial year. {$unpostedCount} journal entries are not posted."
            );
        }
    }
    
    /**
     * Calculate closing balances for all accounts
     */
    private function calculateClosingBalances(FinancialYear $year): array
    {
        $accounts = Account::where('organization_id', $year->organization_id)
            ->where('is_active', true)
            ->where('is_group', false)
            ->get();
            
        $balances = [
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'revenue' => [],
            'expenses' => [],
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_equity' => 0,
            'total_revenue' => 0,
            'total_expenses' => 0,
            'net_profit_loss' => 0,
        ];
        
        foreach ($accounts as $account) {
            // Check if account has getBalanceAsOf method
            if (method_exists($account, 'getBalanceAsOf')) {
                $balance = $account->getBalanceAsOf($year->end_date);
            } else {
                // Fallback to current_balance if method doesn't exist
                $balance = $account->current_balance ?? 0;
            }
            
            if (abs($balance) < 0.0001) {
                continue;
            }
            
            $accountData = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];
            
            switch ($account->type) {
                case 'Asset':
                    $balances['assets'][] = $accountData;
                    $balances['total_assets'] += $balance;
                    break;
                case 'Liability':
                    $balances['liabilities'][] = $accountData;
                    $balances['total_liabilities'] += $balance;
                    break;
                case 'Equity':
                    $balances['equity'][] = $accountData;
                    $balances['total_equity'] += $balance;
                    break;
                case 'Revenue':
                    $balances['revenue'][] = $accountData;
                    $balances['total_revenue'] += $balance;
                    break;
                case 'Expense':
                    $balances['expenses'][] = $accountData;
                    $balances['total_expenses'] += $balance;
                    break;
            }
        }
        
        // Calculate net profit/loss
        $balances['net_profit_loss'] = $balances['total_revenue'] - $balances['total_expenses'];
        
        return $balances;
    }
    
    /**
     * Create closing entries (transfer revenue/expense to retained earnings)
     * 
     * @throws InvalidArgumentException
     */
    private function createClosingEntries(FinancialYear $year, array $balances)
    {
        // Get retained earnings account
        $retainedEarnings = Account::where('organization_id', $year->organization_id)
            ->where('type', 'Equity')
            ->where('name', 'like', '%Retained Earnings%')
            ->first();
            
        if (!$retainedEarnings) {
            throw new InvalidArgumentException('Retained Earnings account not found. Please create it first.');
        }
        
        $journalLines = [];
        
        // Close revenue accounts (credit balance -> debit to close)
        foreach ($balances['revenue'] as $revenue) {
            if ($revenue['balance'] > 0) {
                $journalLines[] = [
                    'account_id' => $revenue['id'],
                    'debit' => $revenue['balance'],
                    'credit' => 0,
                    'line_memo' => "Closing revenue account {$revenue['code']} - {$revenue['name']}",
                ];
            }
        }
        
        // Close expense accounts (debit balance -> credit to close)
        foreach ($balances['expenses'] as $expense) {
            if ($expense['balance'] > 0) {
                $journalLines[] = [
                    'account_id' => $expense['id'],
                    'debit' => 0,
                    'credit' => $expense['balance'],
                    'line_memo' => "Closing expense account {$expense['code']} - {$expense['name']}",
                ];
            }
        }
        
        // Transfer net profit/loss to retained earnings
        if (abs($balances['net_profit_loss']) > 0.0001) {
            if ($balances['net_profit_loss'] > 0) {
                // Net profit: credit retained earnings
                $journalLines[] = [
                    'account_id' => $retainedEarnings->id,
                    'debit' => 0,
                    'credit' => $balances['net_profit_loss'],
                    'line_memo' => "Transfer net profit to retained earnings",
                ];
            } else {
                // Net loss: debit retained earnings
                $journalLines[] = [
                    'account_id' => $retainedEarnings->id,
                    'debit' => abs($balances['net_profit_loss']),
                    'credit' => 0,
                    'line_memo' => "Transfer net loss to retained earnings",
                ];
            }
        }
        
        // Format date properly
        $endDate = $year->end_date instanceof Carbon 
            ? $year->end_date->format('Y-m-d') 
            : (is_string($year->end_date) ? $year->end_date : date('Y-m-d'));
        
        // Create the closing journal
        return $this->journalService->create([
            'organization_id' => $year->organization_id,
            'financial_year_id' => $year->id,
            'date' => $endDate,
            'memo' => "Year-end closing entries for {$year->name}",
            'reference_type' => FinancialYear::class,
            'reference_id' => $year->id,
            'voucher_type_id' => $this->getClosingVoucherType($year->organization_id),
        ], $journalLines, true);
    }
    
    /**
     * Create next financial year
     */
    private function createNextYear(FinancialYear $currentYear, array $data): ?FinancialYear
    {
        // Ensure end_date is Carbon instance
        $endDate = $currentYear->end_date instanceof Carbon 
            ? $currentYear->end_date 
            : Carbon::parse($currentYear->end_date);
        
        // Check if next year already exists
        $nextYearStart = $endDate->copy()->addDay();
        $nextYearEnd = $nextYearStart->copy()->addYear()->subDay();
        
        $existingNextYear = FinancialYear::where('organization_id', $currentYear->organization_id)
            ->where('start_date', $nextYearStart)
            ->first();
            
        if ($existingNextYear) {
            return $existingNextYear;
        }
        
        // Create next year
        $nextYearName = $data['next_year_name'] ?? ($endDate->year + 1) . ' Financial Year';
        
        return FinancialYear::create([
            'organization_id' => $currentYear->organization_id,
            'name' => $nextYearName,
            'start_date' => $nextYearStart,
            'end_date' => $nextYearEnd,
            'is_active' => true,
            'is_closed' => false,
            'status' => 'draft',
        ]);
    }
    
    /**
     * Create opening balance journal for new financial year
     */
    private function createOpeningBalanceJournal(FinancialYear $year, array $closingBalances): void
    {
        // Get opening balances for assets, liabilities, and equity
        $openingLines = [];
        
        // Assets (debit opening balance)
        foreach ($closingBalances['assets'] as $asset) {
            if ($asset['balance'] > 0) {
                $openingLines[] = [
                    'account_id' => $asset['id'],
                    'debit' => $asset['balance'],
                    'credit' => 0,
                    'line_memo' => "Opening balance brought forward",
                ];
            }
        }
        
        // Liabilities (credit opening balance)
        foreach ($closingBalances['liabilities'] as $liability) {
            if ($liability['balance'] > 0) {
                $openingLines[] = [
                    'account_id' => $liability['id'],
                    'debit' => 0,
                    'credit' => $liability['balance'],
                    'line_memo' => "Opening balance brought forward",
                ];
            }
        }
        
        // Equity (credit opening balance)
        foreach ($closingBalances['equity'] as $equity) {
            if ($equity['balance'] > 0) {
                $openingLines[] = [
                    'account_id' => $equity['id'],
                    'debit' => 0,
                    'credit' => $equity['balance'],
                    'line_memo' => "Opening balance brought forward",
                ];
            }
        }
        
        if (empty($openingLines)) {
            return;
        }
        
        // Format date properly
        $startDate = $year->start_date instanceof Carbon 
            ? $year->start_date->format('Y-m-d') 
            : (is_string($year->start_date) ? $year->start_date : date('Y-m-d'));
        
        // Create opening balance journal
        $this->journalService->create([
            'organization_id' => $year->organization_id,
            'financial_year_id' => $year->id,
            'date' => $startDate,
            'memo' => "Opening balances for {$year->name}",
            'reference_type' => FinancialYear::class,
            'reference_id' => $year->id,
            'voucher_type_id' => $this->getOpeningVoucherType($year->organization_id),
        ], $openingLines, true);
        
        // Mark opening balances as posted
        $year->update([
            'opening_balances_posted' => true,
            'opening_balance_total' => array_sum(array_column($closingBalances['assets'], 'balance')),
            'status' => 'open',
        ]);
    }
    
    /**
     * Reopen a closed financial year (admin only, with caution)
     * 
     * @throws InvalidArgumentException
     */
    public function reopenYear(FinancialYear $year, string $reason): FinancialYear
    {
        if (!$year->is_closed) {
            throw new InvalidArgumentException('Financial year is not closed.');
        }
        
        DB::transaction(function () use ($year, $reason) {
            // Check if any transactions exist in next year that depend on this year
            $nextYear = FinancialYear::where('organization_id', $year->organization_id)
                ->where('start_date', '>', $year->end_date)
                ->orderBy('start_date')
                ->first();
                
            if ($nextYear && $nextYear->journals()->exists()) {
                throw new InvalidArgumentException(
                    'Cannot reopen previous year because transactions exist in the next financial year.'
                );
            }
            
            // Get current user name safely
            $user = Auth::user();
            $userName = $user ? $user->name : 'System';
            
            // Reopen the year
            $year->update([
                'is_closed' => false,
                'status' => 'open',
                'closed_by' => null,
                'closed_at' => null,
                'closure_notes' => ($year->closure_notes ? $year->closure_notes . "\n" : '') .
                    "REOPENED: {$reason} by {$userName}",
            ]);
            
            // Delete opening balance journal of next year if exists
            if ($nextYear && $nextYear->opening_balances_posted) {
                $this->reverseOpeningBalanceJournal($nextYear);
            }
        });
        
        return $year;
    }
    
    /**
     * Get opening balance voucher type
     */
    private function getOpeningVoucherType(int $organizationId): int
    {
        $voucherType = VoucherType::firstOrCreate([
            'organization_id' => $organizationId,
            'name' => 'Opening Balance',
            'module' => 'accounts',
        ], [
            'prefix' => 'OPBAL',
            'next_number' => 1,
        ]);
        
        return $voucherType->id;
    }
    
    /**
     * Get closing voucher type
     */
    private function getClosingVoucherType(int $organizationId): int
    {
        $voucherType = VoucherType::firstOrCreate([
            'organization_id' => $organizationId,
            'name' => 'Year End Closing',
            'module' => 'accounts',
        ], [
            'prefix' => 'YECLOSE',
            'next_number' => 1,
        ]);
        
        return $voucherType->id;
    }
    
    /**
     * Reverse opening balance journal (for reopening)
     */
    private function reverseOpeningBalanceJournal(FinancialYear $year): void
    {
        // Find opening balance journal using proper model query
        $openingJournal = \App\Models\Accounts\Journal::where('financial_year_id', $year->id)
            ->where('reference_type', FinancialYear::class)
            ->where('reference_id', $year->id)
            ->where('memo', 'like', 'Opening balances%')
            ->first();
            
        if ($openingJournal) {
            // Safely check if reverse method exists on the model
            if (method_exists($openingJournal, 'reverse')) {
                $openingJournal->reverse();
            } else {
                // If reverse method doesn't exist, delete the journal
                // Ensure we're deleting a model instance, not stdClass
                if ($openingJournal instanceof \Illuminate\Database\Eloquent\Model) {
                    $openingJournal->delete();
                }
            }
        }
        
        $year->update(['opening_balances_posted' => false]);
    }
}
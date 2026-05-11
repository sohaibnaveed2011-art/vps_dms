<?php

namespace App\Services\Accounts;

use App\Models\Accounts\Journal;
use App\Models\Accounts\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounts\JournalLine;
use App\Contracts\FinancialYearInterface;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{    
    public function __construct(protected FinancialYearInterface $financialYearService)
    {}
    
    /**
     * Create a journal entry
     * 
     * @throws ValidationException
     */
    public function create(array $data, array $lines, bool $autoPost = false): Journal
    {
        // Validate before transaction
        $this->validateJournalLines($lines);
        
        // Get active financial year if not provided
        if (empty($data['financial_year_id'])) {
            $activeYear = $this->financialYearService->getActiveYear();
            if (!$activeYear) {
                throw ValidationException::withMessages([
                    'financial_year' => ['No active financial year found. Please create/open a financial year first.']
                ]);
            }
            $data['financial_year_id'] = $activeYear->id;
        }
        
        // Get organization ID if not provided
        if (empty($data['organization_id'])) {
            $data['organization_id'] = $this->getCurrentOrganizationId();
        }
        
        return DB::transaction(function () use ($data, $lines, $autoPost) {
            // Create journal header
            $journal = Journal::create([
                'organization_id' => $data['organization_id'],
                'financial_year_id' => $data['financial_year_id'],
                'voucher_no' => $data['voucher_no'] ?? $this->generateVoucherNumber($data['organization_id']),
                'date' => $data['date'],
                'memo' => $data['memo'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'created_by' => Auth::id(),
                'is_posted' => $autoPost,
                'branch_id' => $data['branch_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'outlet_id' => $data['outlet_id'] ?? null,
            ]);
            
            // Create journal lines
            foreach ($lines as $line) {
                $this->createJournalLine($journal, $line);
            }
            
            // Auto post if requested
            if ($autoPost) {
                $this->post($journal);
            }
            
            return $journal->load('lines.account');
        });
    }
    
    /**
     * Post a journal entry
     * 
     * @throws ValidationException
     */
    public function post(Journal $journal): void
    {
        if ($journal->is_posted) {
            throw ValidationException::withMessages([
                'journal' => ['Journal entry already posted']
            ]);
        }
        
        // Verify financial year is open
        $financialYear = $journal->financialYear;
        if (!$this->financialYearService->canPostJournal($financialYear)) {
            throw ValidationException::withMessages([
                'financial_year' => ['Cannot post to closed or inactive financial year']
            ]);
        }
        
        // Verify entry is balanced
        if (!$journal->isBalanced()) {
            throw ValidationException::withMessages([
                'journal' => ['Journal entry is not balanced. Debits must equal credits.']
            ]);
        }
        
        DB::transaction(function () use ($journal) {
            // Update account current balances
            foreach ($journal->lines as $line) {
                $account = $line->account;
                $balanceChange = 0;
                
                if ($account->normal_balance === 'Debit') {
                    $balanceChange = $line->debit - $line->credit;
                } else {
                    $balanceChange = $line->credit - $line->debit;
                }
                
                $account->increment('current_balance', $balanceChange);
                $account->update(['last_posted_at' => now()]);
            }
            
            // Mark as posted
            $journal->update([
                'is_posted' => true,
            ]);
        });
    }
    
    /**
     * Validate journal lines
     * 
     * @throws ValidationException
     */
    private function validateJournalLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => ['Journal must have at least 2 lines']
            ]);
        }
        
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($lines as $index => $line) {
            // Validate each line has either debit or credit
            $debit = $line['debit'] ?? 0;
            $credit = $line['credit'] ?? 0;
            
            if ($debit <= 0 && $credit <= 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => ['Each journal line must have either debit or credit amount']
                ]);
            }
            
            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => ['Journal line cannot have both debit and credit']
                ]);
            }
            
            // Validate account exists and is active
            $account = Account::find($line['account_id']);
            if (!$account) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => ['Account not found']
                ]);
            }
            
            if (!$account->is_active) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" => ["Account '{$account->name}' is inactive"]
                ]);
            }
            
            $totalDebit += $debit;
            $totalCredit += $credit;
        }
        
        // Check if total debits equal total credits
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw ValidationException::withMessages([
                'total' => ["Journal is not balanced. Debit: {$totalDebit}, Credit: {$totalCredit}"]
            ]);
        }
    }
    
    /**
     * Create journal line
     */
    private function createJournalLine(Journal $journal, array $lineData): JournalLine
    {
        return JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $lineData['account_id'],
            'debit' => $lineData['debit'] ?? 0,
            'credit' => $lineData['credit'] ?? 0,
            'line_memo' => $lineData['memo'] ?? null,
            'created_by' => Auth::id(),
            'branch_id' => $lineData['branch_id'] ?? null,
            'warehouse_id' => $lineData['warehouse_id'] ?? null,
            'outlet_id' => $lineData['outlet_id'] ?? null,
        ]);
    }
    
    /**
     * Generate voucher number
     */
    private function generateVoucherNumber(int $organizationId): string
    {
        $lastJournal = Journal::where('organization_id', $organizationId)
            ->whereYear('created_at', now()->year)
            ->orderBy('id', 'desc')
            ->first();
            
        if ($lastJournal) {
            $lastNumber = intval(substr($lastJournal->voucher_no, -6));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        $prefix = 'JV';
        $year = now()->format('y');
        
        return sprintf("%s%s-%06d", $prefix, $year, $newNumber);
    }
    
    /**
     * Get current organization ID from context
     */
    private function getCurrentOrganizationId(): ?int
    {
        // Try to get from authenticated user
        if (Auth::check() && method_exists(Auth::user(), 'getCurrentOrganizationId')) {
            return Auth::user()->getCurrentOrganizationId();
        }
        
        // Alternative: get from session
        return session('current_organization_id');
    }
}
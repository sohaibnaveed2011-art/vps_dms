<?php

namespace Database\Seeders;

use App\Models\Accounts\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = DB::table('organizations')->value('id');
        
        if (!$organizationId) {
            $this->command->warn('No organization found. Please run OrganizationSeeder first.');
            return;
        }

        // Define accounts with clear level structure
        $accounts = [
            // ========== LEVEL 0: Root Accounts ==========
            // Assets
            ['code' => '1', 'name' => 'Assets', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 0, 'is_group' => true],
            
            // Liabilities
            ['code' => '2', 'name' => 'Liabilities', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 0, 'is_group' => true],
            
            // Equity
            ['code' => '3', 'name' => 'Equity', 'type' => 'Equity', 'normal_balance' => 'Credit', 'level' => 0, 'is_group' => true],
            
            // Revenue
            ['code' => '4', 'name' => 'Revenue', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'level' => 0, 'is_group' => true],
            
            // Expenses
            ['code' => '5', 'name' => 'Expenses', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 0, 'is_group' => true],
            
            // ========== LEVEL 1: Main Categories (Under Assets) ==========
            ['code' => '1.1', 'name' => 'Current Assets', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 1, 'parent_code' => '1', 'is_group' => true],
            ['code' => '1.2', 'name' => 'Fixed Assets', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 1, 'parent_code' => '1', 'is_group' => true],
            
            // ========== LEVEL 1: Main Categories (Under Liabilities) ==========
            ['code' => '2.1', 'name' => 'Current Liabilities', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 1, 'parent_code' => '2', 'is_group' => true],
            ['code' => '2.2', 'name' => 'Long Term Liabilities', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 1, 'parent_code' => '2', 'is_group' => true],
            
            // ========== LEVEL 1: Main Categories (Under Equity) ==========
            ['code' => '3.1', 'name' => 'Owner\'s Equity', 'type' => 'Equity', 'normal_balance' => 'Credit', 'level' => 1, 'parent_code' => '3', 'is_group' => true],
            ['code' => '3.2', 'name' => 'Retained Earnings', 'type' => 'Equity', 'normal_balance' => 'Credit', 'level' => 1, 'parent_code' => '3', 'is_group' => true],
            
            // ========== LEVEL 1: Main Categories (Under Revenue) ==========
            ['code' => '4.1', 'name' => 'Operating Revenue', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'level' => 1, 'parent_code' => '4', 'is_group' => true],
            ['code' => '4.2', 'name' => 'Other Income', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'level' => 1, 'parent_code' => '4', 'is_group' => true],
            
            // ========== LEVEL 1: Main Categories (Under Expenses) ==========
            ['code' => '5.1', 'name' => 'Cost of Sales', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 1, 'parent_code' => '5', 'is_group' => true],
            ['code' => '5.2', 'name' => 'Operating Expenses', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 1, 'parent_code' => '5', 'is_group' => true],
            ['code' => '5.3', 'name' => 'Financial Expenses', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 1, 'parent_code' => '5', 'is_group' => true],
            
            // ========== LEVEL 2: Detail Accounts (Posting Level) ==========
            // Under Current Assets
            ['code' => '1.1.1', 'name' => 'Cash in Hand', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.1', 'is_group' => false],
            ['code' => '1.1.2', 'name' => 'Cash at Bank - PKR', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.1', 'is_group' => false],
            ['code' => '1.1.3', 'name' => 'Cash at Bank - USD', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.1', 'is_group' => false],
            ['code' => '1.1.4', 'name' => 'Accounts Receivable', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.1', 'is_group' => false, 'is_taxable' => true],
            ['code' => '1.1.5', 'name' => 'Inventory', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.1', 'is_group' => false],
            ['code' => '1.1.6', 'name' => 'Prepaid Expenses', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.1', 'is_group' => false],
            
            // Under Fixed Assets
            ['code' => '1.2.1', 'name' => 'Land & Building', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.2', 'is_group' => false],
            ['code' => '1.2.2', 'name' => 'Machinery & Equipment', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.2', 'is_group' => false],
            ['code' => '1.2.3', 'name' => 'Furniture & Fixtures', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.2', 'is_group' => false],
            ['code' => '1.2.4', 'name' => 'Computers & Software', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.2', 'is_group' => false],
            ['code' => '1.2.5', 'name' => 'Vehicles', 'type' => 'Asset', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '1.2', 'is_group' => false],
            ['code' => '1.2.6', 'name' => 'Accumulated Depreciation', 'type' => 'Asset', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '1.2', 'is_group' => false],
            
            // Under Current Liabilities
            ['code' => '2.1.1', 'name' => 'Accounts Payable', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '2.1', 'is_group' => false],
            ['code' => '2.1.2', 'name' => 'Sales Tax Payable', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '2.1', 'is_group' => false, 'is_taxable' => true],
            ['code' => '2.1.3', 'name' => 'Income Tax Payable', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '2.1', 'is_group' => false],
            ['code' => '2.1.4', 'name' => 'Accrued Expenses', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '2.1', 'is_group' => false],
            ['code' => '2.1.5', 'name' => 'Short Term Loans', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '2.1', 'is_group' => false],
            
            // Under Long Term Liabilities
            ['code' => '2.2.1', 'name' => 'Bank Loans', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '2.2', 'is_group' => false],
            ['code' => '2.2.2', 'name' => 'Lease Liabilities', 'type' => 'Liability', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '2.2', 'is_group' => false],
            
            // Under Owner's Equity
            ['code' => '3.1.1', 'name' => 'Share Capital', 'type' => 'Equity', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '3.1', 'is_group' => false],
            ['code' => '3.1.2', 'name' => 'Drawings', 'type' => 'Equity', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '3.1', 'is_group' => false],
            
            // Under Retained Earnings
            ['code' => '3.2.1', 'name' => 'Opening Retained Earnings', 'type' => 'Equity', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '3.2', 'is_group' => false],
            ['code' => '3.2.2', 'name' => 'Current Year Profit/Loss', 'type' => 'Equity', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '3.2', 'is_group' => false],
            
            // Under Operating Revenue
            ['code' => '4.1.1', 'name' => 'Product Sales', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '4.1', 'is_group' => false, 'is_taxable' => true],
            ['code' => '4.1.2', 'name' => 'Service Revenue', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '4.1', 'is_group' => false, 'is_taxable' => true],
            
            // Under Other Income
            ['code' => '4.2.1', 'name' => 'Interest Income', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '4.2', 'is_group' => false],
            ['code' => '4.2.2', 'name' => 'Discount Received', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'level' => 2, 'parent_code' => '4.2', 'is_group' => false],
            
            // Under Cost of Sales
            ['code' => '5.1.1', 'name' => 'COGS - Raw Materials', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.1', 'is_group' => false],
            ['code' => '5.1.2', 'name' => 'COGS - Labor', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.1', 'is_group' => false],
            ['code' => '5.1.3', 'name' => 'Freight & Shipping', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.1', 'is_group' => false],
            
            // Under Operating Expenses
            ['code' => '5.2.1', 'name' => 'Rent Expense', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.2', 'is_group' => false],
            ['code' => '5.2.2', 'name' => 'Utilities Expense', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.2', 'is_group' => false],
            ['code' => '5.2.3', 'name' => 'Salaries & Wages', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.2', 'is_group' => false],
            ['code' => '5.2.4', 'name' => 'Marketing & Advertising', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.2', 'is_group' => false],
            ['code' => '5.2.5', 'name' => 'Office Supplies', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.2', 'is_group' => false],
            ['code' => '5.2.6', 'name' => 'Repairs & Maintenance', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.2', 'is_group' => false],
            ['code' => '5.2.7', 'name' => 'Insurance Expense', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.2', 'is_group' => false],
            ['code' => '5.2.8', 'name' => 'Depreciation Expense', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.2', 'is_group' => false],
            
            // Under Financial Expenses
            ['code' => '5.3.1', 'name' => 'Bank Charges', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.3', 'is_group' => false],
            ['code' => '5.3.2', 'name' => 'Interest Expense', 'type' => 'Expense', 'normal_balance' => 'Debit', 'level' => 2, 'parent_code' => '5.3', 'is_group' => false],
        ];

        // Create a mapping of codes to IDs
        $codeToId = [];

        // Insert accounts in order (Level 0 first, then Level 1, then Level 2)
        foreach ($accounts as $accountData) {
            $parentCode = $accountData['parent_code'] ?? null;
            $parentId = $parentCode ? ($codeToId[$parentCode] ?? null) : null;
            
            // Get values with defaults
            $isGroup = $accountData['is_group'] ?? false;
            $isTaxable = $accountData['is_taxable'] ?? false;
            
            $account = Account::create([
                'organization_id' => $organizationId,
                'parent_id' => $parentId,
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'type' => $accountData['type'],
                'normal_balance' => $accountData['normal_balance'],
                'is_group' => $isGroup,
                'is_active' => true,
                'level' => $accountData['level'],
                'is_taxable' => $isTaxable,
                'currency_code' => 'PKR',
                'description' => $isGroup ? "Group account for {$accountData['name']}" : "Detail account for {$accountData['name']}",
            ]);
            
            $codeToId[$accountData['code']] = $account->id;
        }

        // Show success message with statistics
        $this->command->info('✓ 3-Level Chart of Accounts seeded successfully!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Total accounts created: ' . count($accounts));
        $this->command->info('');
        $this->command->info('📊 Structure Summary:');
        
        // Convert array to collection for counting
        $accountsCollection = collect($accounts);
        
        $level0Count = $accountsCollection->where('level', 0)->count();
        $level1Count = $accountsCollection->where('level', 1)->count();
        $level2Count = $accountsCollection->where('level', 2)->count();
        
        $this->command->info("  • Level 0 (Root Accounts): {$level0Count}");
        $this->command->info("  • Level 1 (Main Categories): {$level1Count}");
        $this->command->info("  • Level 2 (Detail Accounts): {$level2Count}");
        
        // Count by account type
        $this->command->info('');
        $this->command->info('📁 Accounts by Type:');
        $assetCount = $accountsCollection->where('type', 'Asset')->count();
        $liabilityCount = $accountsCollection->where('type', 'Liability')->count();
        $equityCount = $accountsCollection->where('type', 'Equity')->count();
        $revenueCount = $accountsCollection->where('type', 'Revenue')->count();
        $expenseCount = $accountsCollection->where('type', 'Expense')->count();
        
        $this->command->info("  • Assets: {$assetCount}");
        $this->command->info("  • Liabilities: {$liabilityCount}");
        $this->command->info("  • Equity: {$equityCount}");
        $this->command->info("  • Revenue: {$revenueCount}");
        $this->command->info("  • Expenses: {$expenseCount}");
        
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
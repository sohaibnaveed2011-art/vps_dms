<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VoucherTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $organizationId = 1;

        $types = [
            // Sales & Income
            ['prefix' => 'INV',  'name' => 'Sale Invoice',           'module' => 'sale'],
            ['prefix' => 'SO',   'name' => 'Sale Order',             'module' => 'sale'],
            ['prefix' => 'CN',   'name' => 'Credit Note',            'module' => 'sale'],
            ['prefix' => 'GDN',  'name' => 'Goods Delivery Note',    'module' => 'sale'],

            // Purchases & Expenses
            ['prefix' => 'BILL', 'name' => 'Purchase Bill',          'module' => 'purchase'],
            ['prefix' => 'PO',   'name' => 'Purchase Order',         'module' => 'purchase'],
            ['prefix' => 'DN',   'name' => 'Debit Note',             'module' => 'purchase'],
            ['prefix' => 'GRN',  'name' => 'Goods Received Note',    'module' => 'purchase'],

            // Accounts & Finance
            ['prefix' => 'CPV',  'name' => 'Cash Payment Voucher',   'module' => 'accounts'],
            ['prefix' => 'CRV',  'name' => 'Cash Receipt Voucher',   'module' => 'accounts'],
            ['prefix' => 'BPV',  'name' => 'Bank Payment Voucher',   'module' => 'accounts'],
            ['prefix' => 'BRV',  'name' => 'Bank Receipt Voucher',   'module' => 'accounts'],
            ['prefix' => 'JV',   'name' => 'Journal Voucher',        'module' => 'accounts'],
            ['prefix' => 'CONT', 'name' => 'Contra Voucher',         'module' => 'accounts'],

            // HR & Payroll
            ['prefix' => 'SLV',  'name' => 'Salary Voucher',         'module' => 'payroll'],
            ['prefix' => 'BSV',  'name' => 'Bonus Voucher',          'module' => 'payroll'],
            ['prefix' => 'COM',  'name' => 'Commission Voucher',     'module' => 'payroll'],
            ['prefix' => 'ADV',  'name' => 'Employee Advance',       'module' => 'payroll'],
            ['prefix' => 'EXP',  'name' => 'Expense Claim',          'module' => 'payroll'],

            // Additional Inventory
            ['prefix' => 'ST',   'name' => 'Stock Transfer',         'module' => 'inventory'],
            ['prefix' => 'SA',   'name' => 'Stock Adjustment',       'module' => 'inventory'],
        ];

        $data = array_map(function ($type) use ($organizationId, $now) {
            return [
                'organization_id' => $organizationId,
                'name'            => $type['name'],
                'prefix'          => $type['prefix'],
                'module'          => $type['module'],
                'next_number'     => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }, $types);

        DB::table('voucher_types')->insert($data);
    }
}

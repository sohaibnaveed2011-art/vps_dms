<?php

namespace App\Services\Account;

use App\Models\Account\Account;
use App\Models\Account\GlTransaction;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    protected AccountMappingService $accountMapping;

    public function __construct(?AccountMappingService $accountMapping = null)
    {
        $this->accountMapping = $accountMapping ?? new AccountMappingService;
    }

    /**
     * Create GL entries for an invoice with proper account mapping
     * Debit: Accounts Receivable, Credit: Sales Revenue
     * Also create COGS and Inventory adjustment entries if cost_of_goods_sold is tracked
     */
    public function createGlForInvoice($invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $organizationId = $invoice->organization_id ?? null;

            // Get mapped accounts
            $receivableAccount = $this->accountMapping->getReceivableAccount($organizationId);
            $salesAccount = $this->accountMapping->getSalesAccount($organizationId);
            $cogsAccount = $this->accountMapping->getCogsAccount($organizationId);
            $inventoryAccount = $this->accountMapping->getInventoryAccount($organizationId);

            $amount = $invoice->grand_total ?? 0;
            $totalCogs = 0;

            if ($amount <= 0) {
                return;
            }

            // Calculate total COGS from document items
            if ($cogsAccount && $inventoryAccount) {
                $documentItems = \App\Models\Voucher\DocumentItem::where('document_type', get_class($invoice))
                    ->where('document_id', $invoice->id)
                    ->get();

                foreach ($documentItems as $item) {
                    $totalCogs += ($item->cost_of_goods_sold ?? 0) * ($item->quantity ?? 0);
                }
            }

            // Main entry: Debit Receivable, Credit Sales
            if ($receivableAccount && $salesAccount) {
                GlTransaction::create([
                    'account_id' => $receivableAccount->id,
                    'date' => $invoice->date ?? now(),
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => 'Invoice '.($invoice->document_number ?? $invoice->id),
                    'document_number' => $invoice->document_number ?? null,
                    'reference_type' => get_class($invoice),
                    'reference_id' => $invoice->id,
                    'created_by' => $invoice->created_by ?? null,
                ]);

                GlTransaction::create([
                    'account_id' => $salesAccount->id,
                    'date' => $invoice->date ?? now(),
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'Invoice '.($invoice->document_number ?? $invoice->id),
                    'document_number' => $invoice->document_number ?? null,
                    'reference_type' => get_class($invoice),
                    'reference_id' => $invoice->id,
                    'created_by' => $invoice->created_by ?? null,
                ]);
            }

            // COGS entry: Debit COGS, Credit Inventory
            if ($cogsAccount && $inventoryAccount && $totalCogs > 0) {
                GlTransaction::create([
                    'account_id' => $cogsAccount->id,
                    'date' => $invoice->date ?? now(),
                    'debit' => $totalCogs,
                    'credit' => 0,
                    'narration' => 'COGS for Invoice '.($invoice->document_number ?? $invoice->id),
                    'document_number' => $invoice->document_number ?? null,
                    'reference_type' => get_class($invoice),
                    'reference_id' => $invoice->id,
                    'created_by' => $invoice->created_by ?? null,
                ]);

                GlTransaction::create([
                    'account_id' => $inventoryAccount->id,
                    'date' => $invoice->date ?? now(),
                    'debit' => 0,
                    'credit' => $totalCogs,
                    'narration' => 'Inventory reduction for Invoice '.($invoice->document_number ?? $invoice->id),
                    'document_number' => $invoice->document_number ?? null,
                    'reference_type' => get_class($invoice),
                    'reference_id' => $invoice->id,
                    'created_by' => $invoice->created_by ?? null,
                ]);
            }
        });
    }
}

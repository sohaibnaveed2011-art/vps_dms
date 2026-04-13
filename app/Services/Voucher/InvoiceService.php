<?php

namespace App\Services\Voucher;

use App\Models\Voucher\Invoice;
use App\Services\Account\AccountingService;
use App\Services\Inventory\StockService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::query();

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where('document_number', 'like', "%{$q}%");
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Invoice
    {
        return Invoice::find($id);
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);

        return $invoice;
    }

    public function delete(Invoice $invoice): bool
    {
        return (bool) $invoice->delete();
    }

    /**
     * Post the invoice: create stock transactions and GL entries
     */
    public function post(Invoice $invoice): Invoice
    {
        $stockService = new StockService;
        $accountingService = new AccountingService;

        DB::transaction(function () use ($invoice, $stockService, $accountingService) {
            // Create stock transactions
            $stockService->reduceStockForDocument(get_class($invoice), $invoice->id, $invoice->created_by ?? null);

            // Create GL entries
            $accountingService->createGlForInvoice($invoice);

            $invoice->status = 'posted';
            $invoice->save();
        });

        $fresh = $invoice->fresh();

        // broadcast voucher posted for realtime UI
        event(new \App\Events\VoucherPosted([
            'reference_type' => get_class($fresh),
            'reference_id' => $fresh->id,
            'document_type' => 'invoice',
            'organization_id' => $fresh->organization_id ?? null,
            'branch_id' => $fresh->branch_id ?? null,
            'created_by' => $fresh->created_by ?? null,
            'items_count' => method_exists($fresh, 'items') ? $fresh->items()->count() : null,
            'totals' => [
                'net' => $fresh->total ?? $fresh->net_total ?? null,
            ],
        ]));

        return $fresh;
    }
}

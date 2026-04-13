<?php

namespace App\Listeners;

use App\Events\VoucherPosted;
use App\Models\StockTransaction;
use App\Services\StockService;

class HandleVoucherPosted
{
    protected StockService $stockService;

    public function __construct()
    {
        $this->stockService = new StockService;
    }

    /**
     * Handle the event.
     */
    public function handle(VoucherPosted $event): void
    {
        $payload = $event->payload;

        $referenceType = $payload['reference_type'] ?? null;
        $referenceId = $payload['reference_id'] ?? null;
        $documentType = $payload['document_type'] ?? null;

        if (! $referenceType || ! $referenceId) {
            return;
        }

        // Idempotency: if transactions for this reference already exist, skip
        $existsIn = StockTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('type', 'in')
            ->exists();

        $existsOut = StockTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('type', 'out')
            ->exists();

        // Decide what to do based on document type
        if (str_contains($referenceType, 'PurchaseBill') || $documentType === 'purchase_bill') {
            if ($existsIn) {
                return;
            }

            // Increase stock for purchase-like documents
            $this->stockService->increaseStockForDocument($referenceType, (int) $referenceId, $payload['created_by'] ?? null);

            return;
        }

        if (str_contains($referenceType, 'Invoice') || $documentType === 'invoice' || $documentType === 'sale') {
            if ($existsOut) {
                return;
            }

            // Reduce stock for sale-like documents
            $this->stockService->reduceStockForDocument($referenceType, (int) $referenceId, $payload['created_by'] ?? null);

            return;
        }

        if (str_contains($referenceType, 'TransferOrder') || $documentType === 'transfer_order') {
            // Transfer handling may require a dedicated method; for now try increase then reduce guarded by idempotency
            if (! $existsIn) {
                $this->stockService->increaseStockForDocument($referenceType, (int) $referenceId, $payload['created_by'] ?? null);
            }

            if (! $existsOut) {
                $this->stockService->reduceStockForDocument($referenceType, (int) $referenceId, $payload['created_by'] ?? null);
            }
        }
    }
}

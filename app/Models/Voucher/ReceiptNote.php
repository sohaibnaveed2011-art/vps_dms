<?php

namespace App\Models\Vouchers;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptNote extends BaseVoucher
{
    protected $table = 'receipt_notes';

    protected $fillable = [
        'organization_id',
        'purchase_order_id',
        'purchase_bill_id',
        'voucher_type_id',
        'document_number',
        'date',
        'status',
        'submitted_at',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'rejection_details',
        'approval_attempts',
        'resubmitted_at',
        'created_by',
        'reviewed_by',
        'approved_by',
        'updated_by',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class, 'purchase_bill_id');
    }

    public function voucherType(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class);
    }

    /* ======================
     |  Business Logic
     ====================== */

    /**
     * Mark goods as received
     */
    public function markReceived(?int $userId = null): self
    {
        $this->status = 'received';
        $this->save();

        $this->changeStatus('received', 'Goods received at warehouse');

        // Update purchase order status if all items received
        if ($this->purchase_order_id) {
            $this->purchaseOrder->checkAndUpdateStatus();
        }

        return $this;
    }

    /**
     * Mark goods as inspected
     */
    public function markInspected(?int $userId = null): self
    {
        if ($this->status !== 'received') {
            throw new \Exception('Goods must be received before inspection');
        }

        $this->status = 'inspected';
        $this->save();

        $this->changeStatus('inspected', 'Goods inspected and approved');

        return $this;
    }

    /**
     * Mark goods as rejected (quality issues)
     */
    public function markRejected(string $reason, ?array $details = null, ?int $userId = null): self
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->rejection_details = $details;
        $this->rejected_by = $userId ?? auth()->id();
        $this->rejected_at = \Carbon\Carbon::now();
        $this->save();

        $this->changeStatus('rejected', $reason);

        // Create debit note for rejected items
        if ($this->purchase_bill_id) {
            $this->createDebitNoteForRejectedItems($reason);
        }

        return $this;
    }

    /**
     * Create debit note for rejected items
     */
    protected function createDebitNoteForRejectedItems(string $reason): void
    {
        $debitNote = DebitNote::create([
            'organization_id' => $this->organization_id,
            'purchase_bill_id' => $this->purchase_bill_id,
            'supplier_id' => $this->purchaseBill->supplier_id,
            'voucher_type_id' => $this->purchaseBill->voucher_type_id,
            'date' => \Carbon\Carbon::now(),
            'grand_total' => $this->calculateRejectedTotal(),
            'status' => 'draft',
            'financial_year_id' => $this->purchaseBill->financial_year_id,
        ]);

        $debitNote->changeStatus('draft', "Automatic debit note for rejected goods: {$reason}");
    }

    /**
     * Calculate total of rejected items
     */
    protected function calculateRejectedTotal(): float
    {
        // Logic to sum up rejected items from document_items
        return $this->items()
            ->where('notes', 'LIKE', '%rejected%')
            ->sum('line_total');
    }

    /**
     * Check if receipt is fully processed
     */
    public function isFullyProcessed(): bool
    {
        return in_array($this->status, ['inspected', 'rejected']);
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopePendingInspection($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeForPurchaseOrder($query, int $purchaseOrderId)
    {
        return $query->where('purchase_order_id', $purchaseOrderId);
    }

    /* ======================
     |  Accessors
     ====================== */

    public function getTotalReceivedAttribute(): float
    {
        return $this->items()->sum('quantity');
    }

    public function getTotalRejectedAttribute(): float
    {
        return $this->items()
            ->where('notes', 'LIKE', '%rejected%')
            ->sum('quantity');
    }

    public function getInspectionStatusAttribute(): string
    {
        if ($this->status === 'inspected') {
            return 'Passed';
        }

        if ($this->status === 'rejected') {
            return 'Failed';
        }

        return 'Pending';
    }
}
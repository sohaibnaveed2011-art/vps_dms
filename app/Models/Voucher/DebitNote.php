<?php

namespace App\Models\Vouchers;

use App\Models\Accounts\Journal;
use App\Models\Partner\Supplier;
use App\Models\Core\Organization;
use App\Models\Vouchers\VoucherType;
use App\Models\Vouchers\BaseVoucher;
use App\Models\Vouchers\PurchaseBill;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNote extends BaseVoucher
{
    protected $table = 'debit_notes';

    protected $fillable = [
        'organization_id',
        'purchase_bill_id',
        'supplier_id',
        'voucher_type_id',
        'document_number',
        'date',
        'grand_total',
        'status',
        'financial_year_id',
        'journal_id',
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
        'grand_total' => 'decimal:4',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class, 'purchase_bill_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function voucherType(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /* ======================
     |  Business Logic
     ====================== */

    /**
     * Post the debit note (create journal entries)
     */
    public function post(?int $userId = null): self
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Debit note must be approved before posting');
        }

        if ($this->journal_id) {
            throw new \Exception('Debit note already posted');
        }

        // Create journal entry logic here
        // $this->journal_id = $journal->id;

        $this->status = 'posted';
        $this->markApproved($userId);
        $this->save();

        $this->changeStatus('posted', 'Debit note posted to accounting');

        // Update linked purchase bill
        if ($this->purchase_bill_id) {
            $this->purchaseBill->updatePaidAmount();
        }

        return $this;
    }

    /**
     * Cancel the debit note
     */
    public function cancel(string $reason, ?int $userId = null): self
    {
        if ($this->status === 'posted') {
            throw new \Exception('Posted debit note cannot be cancelled. Create a reversing entry instead.');
        }

        $this->status = 'cancelled';
        $this->rejection_reason = $reason;
        $this->rejected_by = $userId ?? auth()->id();
        $this->rejected_at = \Carbon\Carbon::now();
        $this->save();

        $this->changeStatus('cancelled', $reason);

        return $this;
    }

    /**
     * Calculate if debit note exceeds original bill amount
     */
    public function wouldExceedBillAmount(): bool
    {
        if (!$this->purchase_bill_id) {
            return false;
        }

        $totalDebits = DebitNote::where('purchase_bill_id', $this->purchase_bill_id)
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $this->id)
            ->sum('grand_total');

        $newTotal = $totalDebits + $this->grand_total;

        return $newTotal > $this->purchaseBill->grand_total;
    }

    /**
     * Validate before saving
     */
    protected static function booted()
    {
        static::creating(function ($debitNote) {
            if ($debitNote->wouldExceedBillAmount()) {
                throw new \Exception('Debit note total exceeds the original purchase bill amount');
            }
        });
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeForPurchaseBill(Builder $query, int $purchaseBillId)
    {
        return $query->where('purchase_bill_id', $purchaseBillId);
    }

    public function scopeUnposted(Builder $query)
    {
        return $query->whereNull('journal_id')->where('status', 'approved');
    }

    /* ======================
     |  Accessors
     ====================== */

    public function getRemainingCreditAttribute(): float
    {
        if (!$this->purchase_bill_id) {
            return 0;
        }

        $usedDebits = DebitNote::where('purchase_bill_id', $this->purchase_bill_id)
            ->where('status', '!=', 'cancelled')
            ->sum('grand_total');

        return $this->purchaseBill->grand_total - $usedDebits;
    }
}
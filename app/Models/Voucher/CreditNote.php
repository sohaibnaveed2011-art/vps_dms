<?php

namespace App\Models\Vouchers;

use App\Models\Accounts\Journal;
use App\Models\Partner\Customer;
use App\Models\Vouchers\Invoice;
use App\Models\Core\Organization;
use App\Models\Vouchers\VoucherType;
use App\Models\Vouchers\BaseVoucher;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends BaseVoucher
{
    protected $table = 'credit_notes';

    protected $fillable = [
        'organization_id',
        'invoice_id',
        'customer_id',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
     * Post the credit note (create journal entries)
     */
    public function post(?int $userId = null): self
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Credit note must be approved before posting');
        }

        if ($this->journal_id) {
            throw new \Exception('Credit note already posted');
        }

        // Create journal entry logic here
        // $this->journal_id = $journal->id;

        $this->status = 'posted';
        $this->markApproved($userId);
        $this->save();

        $this->changeStatus('posted', 'Credit note posted to accounting');

        // Update linked invoice
        if ($this->invoice_id) {
            $this->invoice->updatePaidAmount();
        }

        return $this;
    }

    /**
     * Cancel the credit note
     */
    public function cancel(string $reason, ?int $userId = null): self
    {
        if ($this->status === 'posted') {
            throw new \Exception('Posted credit note cannot be cancelled. Create a reversing entry instead.');
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
     * Calculate if credit note exceeds original invoice amount
     */
    public function wouldExceedInvoiceAmount(): bool
    {
        if (!$this->invoice_id) {
            return false;
        }

        $totalCredits = CreditNote::where('invoice_id', $this->invoice_id)
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $this->id)
            ->sum('grand_total');

        $newTotal = $totalCredits + $this->grand_total;

        return $newTotal > $this->invoice->grand_total;
    }

    /**
     * Get the remaining credit available on the invoice
     */
    public function getRemainingCredit(): float
    {
        if (!$this->invoice_id) {
            return 0;
        }

        $usedCredits = CreditNote::where('invoice_id', $this->invoice_id)
            ->where('status', '!=', 'cancelled')
            ->sum('grand_total');

        return $this->invoice->grand_total - $usedCredits;
    }

    /**
     * Validate before saving
     */
    protected static function booted()
    {
        static::creating(function ($creditNote) {
            if ($creditNote->wouldExceedInvoiceAmount()) {
                throw new \Exception('Credit note total exceeds the original invoice amount');
            }
        });
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeForInvoice(Builder $query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeUnposted(Builder $query)
    {
        return $query->whereNull('journal_id')->where('status', 'approved');
    }

    /* ======================
     |  Accessors
     ====================== */

    public function getIsLinkedToInvoiceAttribute(): bool
    {
        return !is_null($this->invoice_id);
    }

    public function getRemainingCreditAttribute(): float
    {
        return $this->getRemainingCredit();
    }
}
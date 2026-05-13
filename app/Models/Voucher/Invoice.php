<?php

namespace App\Models\Vouchers;

use App\Models\Core\Branch;
use App\Models\Voucher\PosSession;
use App\Models\Accounts\Journal;
use App\Models\Partner\Customer;
use App\Models\Vouchers\CreditNote;
use App\Models\Vouchers\BaseVoucher;
use App\Models\Vouchers\VoucherType;
use App\Models\Vouchers\ReceiptReference;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends BaseVoucher
{
    protected $table = 'invoices';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'pos_session_id',
        'customer_id',
        'voucher_type_id',
        'currency_code',
        'exchange_rate',
        'document_number',
        'fbr_invoice_number',
        'fbr_pos_fee',
        'date',
        'sub_total',
        'tax_total',
        'paid_amount',
        'due_amount',
        'allocated_amount',
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
        'fully_allocated_at',
        'created_by',
        'reviewed_by',
        'approved_by',
        'updated_by',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'exchange_rate' => 'float',
        'sub_total' => 'float',
        'tax_total' => 'float',
        'paid_amount' => 'float',
        'due_amount' => 'float',
        'allocated_amount' => 'float',
        'grand_total' => 'float',
        'fully_allocated_at' => 'datetime',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
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

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function receiptReferences()
    {
        return $this->morphMany(ReceiptReference::class, 'reference');
    }

    /* ======================
     |  Business Logic
     ====================== */

/**
 * Update the paid amount, due amount, and status based on receipt allocations
 * 
 * @return self
 */
public function updatePaidAmount(): self
{
    // Calculate total paid from receipt references
    $totalPaid = $this->receiptReferences()
        ->whereHas('receipt', function ($query) {
            $query->where('status', 'posted');
        })
        ->sum('amount');
    
    // Update payment amounts
    $this->paid_amount = round((float) $totalPaid, 4);
    $this->allocated_amount = $this->paid_amount;
    
    // Calculate due amount with precision
    $grandTotal = (float) $this->grand_total;
    $paidAmount = (float) $this->paid_amount;
    $dueAmount = $grandTotal - $paidAmount;
    
    // Store with 4 decimal precision (round returns float)
    $this->due_amount = round($dueAmount, 4);
    
    // Update status based on payment status
    $this->updateStatusFromPayment();
    
    // Save without triggering events
    $this->saveQuietly();
    
    return $this;
}

/**
 * Update invoice status based on payment amount
 */
protected function updateStatusFromPayment(): void
{
    $tolerance = 0.0001; // Tolerance for floating point comparison
    $isFullyPaid = abs((float) $this->due_amount) <= $tolerance;
    
    if ($this->status === 'cancelled') {
        return;
    }
    
    if ($isFullyPaid && $this->status !== 'paid') {
        $this->status = 'paid';
        $this->fully_allocated_at = \Carbon\Carbon::now();
    } elseif (!$isFullyPaid && $this->due_amount > 0 && $this->status === 'paid') {
        $this->status = 'posted';
        $this->fully_allocated_at = null;
    }
}

    public function getBalanceAttribute(): float
    {
        return $this->grand_total - $this->paid_amount;
    }

    public function isFullyPaid(): bool
    {
        return $this->balance <= 0;
    }
}
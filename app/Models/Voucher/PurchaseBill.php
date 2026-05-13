<?php

namespace App\Models\Vouchers;

use App\Models\Core\Branch;
use App\Models\Parties\Supplier;
use App\Models\Accounting\Journal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseBill extends BaseVoucher
{
    protected $table = 'purchase_bills';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'supplier_id',
        'voucher_type_id',
        'document_number',
        'currency_code',
        'exchange_rate',
        'supplier_invoice_number',
        'date',
        'grand_total',
        'paid_amount',
        'due_amount',
        'allocated_amount',
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
        'exchange_rate' => 'decimal:6',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    public function debitNotes()
    {
        return $this->hasMany(DebitNote::class);
    }

    public function paymentReferences()
    {
        return $this->morphMany(PaymentReference::class, 'reference');
    }

    /* ======================
     |  Business Logic
     ====================== */

    public function updatePaidAmount(): self
    {
        $this->paid_amount = $this->paymentReferences()->sum('amount');
        $this->allocated_amount = $this->paid_amount;
        $this->due_amount = $this->grand_total - $this->paid_amount;

        if ($this->due_amount <= 0 && $this->status !== 'cancelled') {
            $this->status = 'paid';
            $this->fully_allocated_at = now();
        }

        $this->saveQuietly();

        return $this;
    }
}
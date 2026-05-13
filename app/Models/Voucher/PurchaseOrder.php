<?php

namespace App\Models\Vouchers;

use App\Models\Core\Branch;
use App\Models\Parties\Supplier;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends BaseVoucher
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'supplier_id',
        'voucher_type_id',
        'financial_year_id',
        'document_number',
        'order_date',
        'expected_receipt_date',
        'grand_total',
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
        'order_date' => 'date',
        'expected_receipt_date' => 'date',
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

    public function receiptNotes()
    {
        return $this->hasMany(ReceiptNote::class, 'purchase_order_id');
    }

    public function purchaseBills()
    {
        return $this->hasMany(PurchaseBill::class, 'purchase_order_id');
    }
}
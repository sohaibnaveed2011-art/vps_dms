<?php

namespace App\Models\Vouchers;

use App\Models\Core\Branch;
use App\Models\Core\Outlet;
use App\Models\Core\Warehouse;
use App\Models\Partner\Customer;
use App\Models\Voucher\DeliveryNote;
use App\Models\Voucher\Invoice;
use App\Models\Vouchers\BaseVoucher;
use App\Models\Vouchers\VoucherType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleOrder extends BaseVoucher
{
    protected $table = 'sale_orders';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'warehouse_id',
        'outlet_id',
        'financial_year_id',
        'customer_id',
        'voucher_type_id',
        'document_number',
        'order_date',
        'delivery_date',
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
        'delivery_date' => 'date',
        'grand_total' => 'decimal:4',
        'approval_attempts' => 'integer',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function voucherType(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'sale_order_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'sale_order_id');
    }

    /* ======================
     |  Business Logic
     ====================== */

    public function calculateTotal(): float
    {
        return $this->items->sum('line_total');
    }

    public function updateGrandTotal(): self
    {
        $this->grand_total = $this->calculateTotal();
        $this->saveQuietly();

        return $this;
    }
}
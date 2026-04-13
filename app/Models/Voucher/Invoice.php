<?php

namespace App\Models\Voucher;

use App\Models\Account\ReceiptAllocation;
use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\Partner\Customer;
use App\Traits\HasUserTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, HasUserTimestamps, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'branch_id', // Nullable in schema for optional hierarchy
        'pos_session_id',
        'customer_id',
        'voucher_type_id',
        'document_number',
        'fbr_invoice_number',
        'date',
        'grand_total',
        'status',
        'created_by',
        'reviewed_by',
        'approved_by',
        'updated_by',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'grand_total' => 'decimal:4',
        'date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

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

    // FINANCIAL & INVENTORY LINKS
    public function items(): MorphMany // Line items on the invoice
    {
        return $this->morphMany(DocumentItem::class, 'document');
    }

    public function allocations(): HasMany // How much of the invoice is covered by receipts
    {
        return $this->hasMany(ReceiptAllocation::class);
    }

    public function creditNotes(): HasMany // Returns issued against this invoice
    {
        return $this->hasMany(CreditNote::class);
    }
}

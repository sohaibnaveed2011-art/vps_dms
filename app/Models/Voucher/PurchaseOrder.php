<?php

namespace App\Models\Voucher;

use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\Partner\Supplier;
use App\Traits\HasUserTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, HasUserTimestamps;

    protected $fillable = [
        'organization_id',
        'branch_id', // Nullable in schema for optional hierarchy
        'supplier_id',
        'voucher_type_id',
        'document_number',
        'order_date',
        'expected_receipt_date',
        'grand_total',
        'status',
        'created_by', // Note: used as created_by in fillable, but requested_by in logic is common
        'reviewed_by',
        'approved_by',
        'updated_by',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_receipt_date' => 'date',
        'grand_total' => 'decimal:4',
    ];

    // Relationships
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); } // Handles nullable branch_id
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function voucherType(): BelongsTo { return $this->belongsTo(VoucherType::class); }

    // FINANCIAL & INVENTORY LINKS
    public function items(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'document');
    }

    public function receiptNotes(): HasMany // Links to Good Receipt Notes (GRN)
    {
        return $this->hasMany(ReceiptNote::class);
    }
}

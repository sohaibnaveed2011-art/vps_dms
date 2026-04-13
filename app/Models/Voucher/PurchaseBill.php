<?php

namespace App\Models\Voucher;

use App\Models\Account\PaymentAllocation;
use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\Partner\Supplier;
use App\Models\User;
use App\Traits\HasUserTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseBill extends Model
{
    use HasFactory, SoftDeletes, HasUserTimestamps;

    protected $fillable = [
        'organization_id',
        'branch_id', // Nullable in schema for optional hierarchy
        'supplier_id',
        'voucher_type_id',
        'document_number',
        'supplier_invoice_number',
        'date',
        'grand_total',
        'status',
        'created_by',
        'reviewed_by',
        'updated_by',
        'approved_at',
        'reviewed_at',
    ];

    protected $casts = [
        'date' => 'date',
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

    public function allocations(): HasMany // Links to payment allocations
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function debitNotes(): HasMany // Returns issued against this bill
    {
        return $this->hasMany(DebitNote::class);
    }
}

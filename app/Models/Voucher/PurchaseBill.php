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

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $branch_id
 * @property int $supplier_id
 * @property int $voucher_type_id
 * @property string $document_number
 * @property string $currency_code
 * @property numeric $exchange_rate
 * @property string|null $supplier_invoice_number
 * @property \Illuminate\Support\Carbon $date
 * @property numeric $grand_total
 * @property numeric $paid_amount
 * @property numeric $due_amount
 * @property string $status
 * @property int $financial_year_id
 * @property int|null $journal_id
 * @property int|null $created_by
 * @property int|null $reviewed_by
 * @property int|null $approved_by
 * @property int|null $updated_by
 * @property string|null $reviewed_at
 * @property string|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $approver
 * @property-read Branch|null $branch
 * @property-read User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher\DebitNote> $debitNotes
 * @property-read int|null $debit_notes_count
 * @property-read User|null $editor
 * @property-read Organization $organization
 * @property-read User|null $reviewer
 * @property-read Supplier $supplier
 * @property-read \App\Models\Voucher\VoucherType $voucherType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereDueAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereFinancialYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereSupplierInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseBill withoutTrashed()
 * @mixin \Eloquent
 */
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

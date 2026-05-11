<?php

namespace App\Models\Voucher;

use App\Models\Core\Branch;
use App\Models\Partner\Customer;
use App\Traits\HasUserTimestamps;
use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
// use App\Models\Account\ReceiptAllocation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $branch_id
 * @property int|null $pos_session_id
 * @property int $customer_id
 * @property int $voucher_type_id
 * @property string $currency_code
 * @property numeric $exchange_rate
 * @property string $document_number
 * @property string|null $fbr_invoice_number
 * @property string|null $fbr_pos_fee
 * @property \Illuminate\Support\Carbon $date
 * @property numeric $sub_total
 * @property numeric $tax_total
 * @property numeric $paid_amount
 * @property numeric $due_amount
 * @property numeric $grand_total
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
 * @property-read \App\Models\User|null $approver
 * @property-read Branch|null $branch
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher\CreditNote> $creditNotes
 * @property-read int|null $credit_notes_count
 * @property-read Customer $customer
 * @property-read \App\Models\User|null $editor
 * @property-read Organization $organization
 * @property-read \App\Models\Voucher\PosSession|null $posSession
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\Voucher\VoucherType $voucherType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereFbrInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereFbrPosFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereFinancialYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePosSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTaxTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withoutTrashed()
 * @mixin \Eloquent
 */
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
        'fbr_pos_fee',
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

    // public function allocations(): HasMany // How much of the invoice is covered by receipts
    // {
    //     return $this->hasMany(ReceiptAllocation::class);
    // }

    public function creditNotes(): HasMany // Returns issued against this invoice
    {
        return $this->hasMany(CreditNote::class);
    }
}

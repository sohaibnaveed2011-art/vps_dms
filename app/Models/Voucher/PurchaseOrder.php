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

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $branch_id
 * @property int $supplier_id
 * @property int $voucher_type_id
 * @property int|null $financial_year_id
 * @property string $document_number
 * @property \Illuminate\Support\Carbon $order_date
 * @property \Illuminate\Support\Carbon|null $expected_receipt_date
 * @property numeric $grand_total
 * @property string $status
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
 * @property-read \App\Models\User|null $editor
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher\ReceiptNote> $receiptNotes
 * @property-read int|null $receipt_notes_count
 * @property-read \App\Models\User|null $reviewer
 * @property-read Supplier $supplier
 * @property-read \App\Models\Voucher\VoucherType $voucherType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereExpectedReceiptDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereFinancialYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder withoutTrashed()
 * @mixin \Eloquent
 */
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

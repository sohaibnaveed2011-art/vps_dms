<?php

namespace App\Models\Voucher;

use App\Models\Account\GlTransaction;
use App\Models\Core\Organization;
use App\Models\Inventory\StockTransaction;
use App\Models\Partner\Supplier;
use App\Traits\HasUserTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $purchase_bill_id
 * @property int $supplier_id
 * @property int $voucher_type_id
 * @property string $document_number
 * @property \Illuminate\Support\Carbon $date
 * @property numeric $grand_total
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
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $editor
 * @property-read Organization $organization
 * @property-read \App\Models\Voucher\PurchaseBill|null $purchaseBill
 * @property-read \App\Models\User|null $reviewer
 * @property-read Supplier $supplier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereFinancialYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote wherePurchaseBillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DebitNote withoutTrashed()
 * @mixin \Eloquent
 */
class DebitNote extends Model
{
    use HasFactory, HasUserTimestamps, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'purchase_bill_id',
        'supplier_id',
        'document_number',
        'date',
        'grand_total',
        'created_by',
        'reviewed_by',
        'approved_by',
        'updated_by',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'grand_total' => 'decimal:4',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'document');
    }

    /**
     * Get the GL entries posted as a result of this Debit Note.
     */
    public function glTransactions(): MorphMany
    {
        return $this->morphMany(GlTransaction::class, 'reference');
    }

    /**
     * Get the stock transactions (inventory OUT) posted as a result of this return.
     */
    public function stockTransactions(): MorphMany
    {
        return $this->morphMany(StockTransaction::class, 'reference');
    }
}

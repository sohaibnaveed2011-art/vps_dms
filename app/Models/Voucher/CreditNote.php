<?php

namespace App\Models\Voucher;

use App\Models\Account\GlTransaction;
use App\Models\Core\Organization;
use App\Models\Inventory\StockTransaction;
use App\Models\Partner\Customer;
use App\Traits\HasUserTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $invoice_id
 * @property int $customer_id
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
 * @property-read Customer $customer
 * @property-read \App\Models\User|null $editor
 * @property-read \App\Models\Voucher\Invoice|null $invoice
 * @property-read Organization $organization
 * @property-read \App\Models\User|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereFinancialYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditNote withoutTrashed()
 * @mixin \Eloquent
 */
class CreditNote extends Model
{
    use HasFactory, SoftDeletes, HasUserTimestamps;

    protected $fillable = [
        'organization_id',
        'invoice_id',
        'customer_id',
        'document_number',
        'organization_id',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'document');
    }

    /**
     * Get the GL entries posted as a result of this Credit Note.
     */
    public function glTransactions(): MorphMany
    {
        return $this->morphMany(GlTransaction::class, 'reference');
    }

    /**
     * Get the stock transactions (inventory IN) posted as a result of this return.
     */
    public function stockTransactions(): MorphMany
    {
        return $this->morphMany(StockTransaction::class, 'reference');
    }
}

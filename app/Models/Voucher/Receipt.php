<?php

namespace App\Models\Voucher;

use App\Models\Account\Account;
use App\Models\Account\GlTransaction;
use App\Models\Account\ReceiptAllocation;
use App\Models\Core\Organization;
use App\Models\Partner\Customer;
use App\Models\User;
use App\Traits\HasUserTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $branch_id
 * @property int $financial_year_id
 * @property int $voucher_type_id
 * @property int $customer_id
 * @property string $reference_type
 * @property int $reference_id
 * @property string $document_number
 * @property numeric $amount
 * @property \Illuminate\Support\Carbon $date
 * @property int $account_id
 * @property string|null $reference_number
 * @property int $is_posted
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
 * @property-read User|null $creator
 * @property-read Customer $customer
 * @property-read User|null $editor
 * @property-read Organization $organization
 * @property-read Model|\Eloquent $reference
 * @property-read User|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereFinancialYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereIsPosted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt withoutTrashed()
 * @mixin \Eloquent
 */
class Receipt extends Model
{
    use HasFactory, SoftDeletes, HasUserTimestamps;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'reference_type',
        'reference_id',
        'amount',
        'date',
        'account_id',
        'reference_number',
        'created_by',
        'reviewed_by',
        'approved_by',
        'updated_by',
        'reviwed_at',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'date' => 'date',
    ];

    // Relationships
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    
    public function reference(): MorphTo // Links to the source document (Invoice/SaleOrder)
    {
        return $this->morphTo();
    }

    public function allocations(): HasMany // Links to how this receipt was applied to invoices
    {
        return $this->hasMany(ReceiptAllocation::class);
    }

    public function glTransactions(): MorphMany // Links to GL entries where this Receipt is the reference
    {
        return $this->morphMany(GlTransaction::class, 'reference');
    }
}

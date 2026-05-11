<?php

namespace App\Models\Voucher;

use App\Models\Account\Account;
use App\Models\Account\GlTransaction;
use App\Models\Account\PaymentAllocation;
use App\Models\Core\Organization;
use App\Models\Partner\Supplier;
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
 * @property int $supplier_id
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
 * @property-read User|null $editor
 * @property-read Organization $organization
 * @property-read Model|\Eloquent $reference
 * @property-read User|null $reviewer
 * @property-read Supplier $supplier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereFinancialYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereIsPosted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment withoutTrashed()
 * @mixin \Eloquent
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes, HasUserTimestamps;

    protected $fillable = [
        'organization_id',
        'supplier_id',
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
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'date' => 'date',
    ];

    // Relationships
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    
    public function reference(): MorphTo { return $this->morphTo(); } // Links to PurchaseBill or other source

    public function allocations(): HasMany // Links to PaymentAllocation pivot table
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function glTransactions(): MorphMany // Links to GL entries where this Payment is the reference
    {
        return $this->morphMany(GlTransaction::class, 'reference');
    }
}

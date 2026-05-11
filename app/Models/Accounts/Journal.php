<?php

namespace App\Models\Accounts;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Core\Branch;
use App\Models\Core\Outlet;
use App\Models\Core\Warehouse;
use App\Models\Core\Organization;
use Illuminate\Support\Facades\DB;
use App\Models\Core\FinancialYear;
use App\Events\Accounts\JournalPosted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $financial_year_id
 * @property int|null $branch_id
 * @property int|null $warehouse_id
 * @property int|null $outlet_id
 * @property string $voucher_no
 * @property string|null $document_number
 * @property \Illuminate\Support\Carbon $date
 * @property string $reference_type
 * @property int $reference_id
 * @property bool $is_posted
 * @property bool $is_reversed
 * @property string|null $memo
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Branch|null $branch
 * @property-read User|null $createdBy
 * @property-read FinancialYear $financialYear
 * @property-read int $line_count
 * @property-read string $status
 * @property-read string $status_color
 * @property-read float $total_credit
 * @property-read float $total_debit
 * @property-read string $voucher_number_formatted
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Accounts\JournalLine> $lines
 * @property-read int|null $lines_count
 * @property-read Organization $organization
 * @property-read Outlet|null $outlet
 * @property-read Model|\Eloquent $reference
 * @property-read Warehouse|null $warehouse
 * @method static Builder<static>|Journal forFinancialYear(int $financialYearId)
 * @method static Builder<static>|Journal forOrganization(int $organizationId)
 * @method static Builder<static>|Journal inDateRange(string $from, string $to)
 * @method static Builder<static>|Journal newModelQuery()
 * @method static Builder<static>|Journal newQuery()
 * @method static Builder<static>|Journal notReversed()
 * @method static Builder<static>|Journal ofVoucherType(string $referenceType)
 * @method static Builder<static>|Journal onlyTrashed()
 * @method static Builder<static>|Journal posted()
 * @method static Builder<static>|Journal query()
 * @method static Builder<static>|Journal unposted()
 * @method static Builder<static>|Journal whereBranchId($value)
 * @method static Builder<static>|Journal whereCreatedAt($value)
 * @method static Builder<static>|Journal whereCreatedBy($value)
 * @method static Builder<static>|Journal whereDate($value)
 * @method static Builder<static>|Journal whereDeletedAt($value)
 * @method static Builder<static>|Journal whereDocumentNumber($value)
 * @method static Builder<static>|Journal whereFinancialYearId($value)
 * @method static Builder<static>|Journal whereId($value)
 * @method static Builder<static>|Journal whereIsPosted($value)
 * @method static Builder<static>|Journal whereIsReversed($value)
 * @method static Builder<static>|Journal whereMemo($value)
 * @method static Builder<static>|Journal whereOrganizationId($value)
 * @method static Builder<static>|Journal whereOutletId($value)
 * @method static Builder<static>|Journal whereReferenceId($value)
 * @method static Builder<static>|Journal whereReferenceType($value)
 * @method static Builder<static>|Journal whereUpdatedAt($value)
 * @method static Builder<static>|Journal whereVoucherNo($value)
 * @method static Builder<static>|Journal whereWarehouseId($value)
 * @method static Builder<static>|Journal withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Journal withoutTrashed()
 * @mixin \Eloquent
 */
class Journal extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'journals';

    protected $fillable = [
        'organization_id',
        'financial_year_id',
        'branch_id',
        'warehouse_id',
        'outlet_id',
        'voucher_no',
        'document_number',
        'date',
        'reference_type',
        'reference_id',
        'is_posted',
        'is_reversed',
        'memo',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_posted' => 'boolean',
        'is_reversed' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relationships with proper return types
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Polymorphic reference
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Helper Methods
    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.0001;
    }

    public function canBePosted(): bool
    {
        return !$this->is_posted && 
               !$this->is_reversed && 
               $this->isBalanced() &&
               $this->financialYear->canPostJournal();
    }

    public function canBeReversed(): bool
    {
        return $this->is_posted && !$this->is_reversed;
    }

    public function post(): void
    {
        if (!$this->canBePosted()) {
            throw new Exception('Journal cannot be posted');
        }

        DB::transaction(function () {
            $this->update(['is_posted' => true]);
            
            // Update account balances or trigger events
            event(new JournalPosted($this));
        });
    }

    public function reverse(): self
    {
        if (!$this->canBeReversed()) {
            throw new Exception('Journal cannot be reversed');
        }

        DB::transaction(function () {
            $reversal = $this->replicate();
            $reversal->voucher_no = $this->voucher_no . '-REV-' . date('YmdHis');
            $reversal->is_posted = false;
            $reversal->is_reversed = false;
            $reversal->memo = 'Reversal of journal #' . $this->voucher_no;
            $reversal->date = now();
            $reversal->save();

            foreach ($this->lines as $line) {
                $reversalLine = $line->replicate();
                $reversalLine->journal_id = $reversal->id;
                $reversalLine->debit = $line->credit;
                $reversalLine->credit = $line->debit;
                $reversalLine->save();
            }

            $this->update(['is_reversed' => true]);
        });

        return $this;
    }

    /**
     * Scope a query to only include posted journals
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('is_posted', true);
    }

    /**
     * Scope a query to only include unposted journals
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnposted(Builder $query): Builder
    {
        return $query->where('is_posted', false);
    }

    /**
     * Scope a query to only include non-reversed journals
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotReversed(Builder $query): Builder
    {
        return $query->where('is_reversed', false);
    }

    /**
     * Scope a query to filter by date range
     *
     * @param Builder $query
     * @param string $from
     * @param string $to
     * @return Builder
     */
    public function scopeInDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Scope a query to filter by voucher type (reference_type)
     *
     * @param Builder $query
     * @param string $referenceType
     * @return Builder
     */
    public function scopeOfVoucherType(Builder $query, string $referenceType): Builder
    {
        return $query->where('reference_type', $referenceType);
    }

    /**
     * Scope a query to filter by financial year
     *
     * @param Builder $query
     * @param int $financialYearId
     * @return Builder
     */
    public function scopeForFinancialYear(Builder $query, int $financialYearId): Builder
    {
        return $query->where('financial_year_id', $financialYearId);
    }

    /**
     * Scope a query to filter by organization
     *
     * @param Builder $query
     * @param int $organizationId
     * @return Builder
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    // Additional helper methods
    public function getVoucherNumberFormattedAttribute(): string
    {
        return str_pad($this->voucher_no, 10, '0', STR_PAD_LEFT);
    }

    public function getStatusAttribute(): string
    {
        if ($this->is_reversed) {
            return 'Reversed';
        }
        if ($this->is_posted) {
            return 'Posted';
        }
        return 'Draft';
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->is_reversed) {
            return 'red';
        }
        if ($this->is_posted) {
            return 'green';
        }
        return 'yellow';
    }

    public function canBeEdited(): bool
    {
        return !$this->is_posted && !$this->is_reversed;
    }

    public function canBeDeleted(): bool
    {
        return !$this->is_posted && !$this->is_reversed;
    }

    public function getLineCountAttribute(): int
    {
        return $this->lines()->count();
    }

    /**
     * Get summary of the journal entry as an array
     */
    public function getSummary(): array
    {
        return [
            'voucher_no' => $this->voucher_no,
            'date' => $this->getFormattedDate(),
            'total_debit' => (float) $this->total_debit,
            'total_credit' => (float) $this->total_credit,
            'is_balanced' => $this->isBalanced(),
            'status' => $this->status,
            'line_count' => $this->getLineCount(),
        ];
    }

    /**
     * Get formatted date
     */
    public function getFormattedDate(string $format = 'Y-m-d'): string
    {
        if ($this->date instanceof Carbon) {
            return $this->date->format($format);
        }
        
        // If date is string, try to parse it
        try {
            return Carbon::parse($this->date)->format($format);
        } catch (Exception $e) {
            return (string) $this->date;
        }
    }
}
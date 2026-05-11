<?php

namespace App\Models\Core;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Accounts\Journal;
use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property bool $is_active
 * @property bool $is_closed
 * @property string $period_type
 * @property int|null $period_number
 * @property int|null $parent_period_id
 * @property string $status
 * @property int|null $closed_by
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property numeric $opening_balance_total
 * @property bool $opening_balances_posted
 * @property string|null $closure_notes
 * @property array<array-key, mixed>|null $closure_summary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $approvedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FinancialYear> $children
 * @property-read int|null $children_count
 * @property-read User|null $closedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Journal> $journals
 * @property-read int|null $journals_count
 * @property-read Organization $organization
 * @property-read FinancialYear|null $parent
 * @method static Builder<static>|FinancialYear active()
 * @method static Builder<static>|FinancialYear forDate($date)
 * @method static Builder<static>|FinancialYear newModelQuery()
 * @method static Builder<static>|FinancialYear newQuery()
 * @method static Builder<static>|FinancialYear onlyTrashed()
 * @method static Builder<static>|FinancialYear open()
 * @method static Builder<static>|FinancialYear query()
 * @method static Builder<static>|FinancialYear whereApprovedAt($value)
 * @method static Builder<static>|FinancialYear whereApprovedBy($value)
 * @method static Builder<static>|FinancialYear whereClosedAt($value)
 * @method static Builder<static>|FinancialYear whereClosedBy($value)
 * @method static Builder<static>|FinancialYear whereClosureNotes($value)
 * @method static Builder<static>|FinancialYear whereClosureSummary($value)
 * @method static Builder<static>|FinancialYear whereCreatedAt($value)
 * @method static Builder<static>|FinancialYear whereDeletedAt($value)
 * @method static Builder<static>|FinancialYear whereEndDate($value)
 * @method static Builder<static>|FinancialYear whereId($value)
 * @method static Builder<static>|FinancialYear whereIsActive($value)
 * @method static Builder<static>|FinancialYear whereIsClosed($value)
 * @method static Builder<static>|FinancialYear whereName($value)
 * @method static Builder<static>|FinancialYear whereOpeningBalanceTotal($value)
 * @method static Builder<static>|FinancialYear whereOpeningBalancesPosted($value)
 * @method static Builder<static>|FinancialYear whereOrganizationId($value)
 * @method static Builder<static>|FinancialYear whereParentPeriodId($value)
 * @method static Builder<static>|FinancialYear wherePeriodNumber($value)
 * @method static Builder<static>|FinancialYear wherePeriodType($value)
 * @method static Builder<static>|FinancialYear whereStartDate($value)
 * @method static Builder<static>|FinancialYear whereStatus($value)
 * @method static Builder<static>|FinancialYear whereUpdatedAt($value)
 * @method static Builder<static>|FinancialYear withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|FinancialYear withoutTrashed()
 * @mixin \Eloquent
 */
class FinancialYear extends Model
{
    use SoftDeletes;
    protected $table = 'financial_years';
    protected $fillable = [
        'organization_id',
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'is_active',
        'period_type',
        'period_number',
        'parent_period_id',
        'status',
        'closed_by',
        'closed_at',
        'approved_by',
        'approved_at',
        'opening_balance_total',
        'opening_balances_posted',
        'closure_notes',
        'closure_summary',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
        'is_active' => 'boolean',
        'opening_balances_posted' => 'boolean',
        'opening_balance_total' => 'decimal:4',
        'closure_summary' => 'array',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'parent_period_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FinancialYear::class, 'parent_period_id');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes with proper type hints
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open')->where('is_closed', false);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('status', 'open');
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date);
    }

    // Helper Methods - Safe version with null checks
    public function isOpen(): bool
    {
        return !$this->is_closed && $this->status === 'open' && $this->is_active;
    }

    public function canPostJournal(): bool
    {
        return $this->isOpen() && !$this->is_closed;
    }

    public function canBeClosed(): bool
    {
        return $this->isOpen() && !$this->is_closed;
    }

    public function getPeriodRange(): string
    {
        $start = $this->start_date ? Carbon::parse($this->start_date) : null;
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;
        
        if (!$start || !$end) {
            return 'Invalid date range';
        }
        
        return $start->format('d M Y') . ' - ' . $end->format('d M Y');
    }

    public function getDaysRemaining(): int
    {
        if ($this->is_closed) {
            return 0;
        }
        
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;
        
        if (!$end) {
            return 0;
        }
        
        $today = Carbon::now();
        
        if ($today->gt($end)) {
            return 0;
        }
        
        return $today->diffInDays($end);
    }

    public function getProgressPercentage(): float
    {
        $start = $this->start_date ? Carbon::parse($this->start_date) : null;
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;
        
        if (!$start || !$end) {
            return 0;
        }
        
        $totalDays = $start->diffInDays($end);
        
        if ($totalDays <= 0) {
            return 0;
        }
        
        $today = Carbon::now();
        
        if ($today->lt($start)) {
            return 0;
        }
        
        if ($today->gt($end)) {
            return 100;
        }
        
        $daysPassed = $start->diffInDays($today);
        $percentage = ($daysPassed / $totalDays) * 100;
        
        return round(min(100, max(0, $percentage)), 2);
    }

    public function getFormattedStartDate(string $format = 'Y-m-d'): string
    {
        $start = $this->start_date ? Carbon::parse($this->start_date) : null;
        
        if (!$start) {
            return '';
        }
        
        return $start->format($format);
    }

    public function getFormattedEndDate(string $format = 'Y-m-d'): string
    {
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;
        
        if (!$end) {
            return '';
        }
        
        return $end->format($format);
    }

    public function containsDate($date): bool
    {
        $dateToCheck = $date instanceof Carbon ? $date : Carbon::parse($date);
        $start = $this->start_date ? Carbon::parse($this->start_date) : null;
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;
        
        if (!$start || !$end) {
            return false;
        }
        
        return $dateToCheck->between($start, $end);
    }

    public function getNextYear(): ?FinancialYear
    {
        if (!$this->end_date) {
            return null;
        }
        
        $endDate = Carbon::parse($this->end_date);
        
        return FinancialYear::where('organization_id', $this->organization_id)
            ->where('start_date', '>', $endDate->format('Y-m-d'))
            ->orderBy('start_date')
            ->first();
    }

    public function getPreviousYear(): ?FinancialYear
    {
        if (!$this->start_date) {
            return null;
        }
        
        $startDate = Carbon::parse($this->start_date);
        
        return FinancialYear::where('organization_id', $this->organization_id)
            ->where('end_date', '<', $startDate->format('Y-m-d'))
            ->orderBy('end_date', 'desc')
            ->first();
    }
    
    // Accessors to ensure Carbon instances
    public function getStartDateAttribute($value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }
    
    public function getEndDateAttribute($value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }
}
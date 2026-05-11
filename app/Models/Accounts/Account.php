<?php

namespace App\Models\Accounts;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $parent_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property int $level
 * @property string $currency_code
 * @property numeric $opening_balance
 * @property \Illuminate\Support\Carbon|null $opening_balance_date
 * @property float $current_balance
 * @property bool $is_taxable
 * @property bool $automatic_postings_disabled
 * @property string $type
 * @property string|null $normal_balance
 * @property bool $is_group
 * @property bool $is_active
 * @property string|null $last_posted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $children
 * @property-read int|null $children_count
 * @property-read string $full_code
 * @property-read string $hierarchy
 * @property-read string $opening_balance_formatted
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Accounts\JournalLine> $journalLines
 * @property-read int|null $journal_lines_count
 * @property-read Organization $organization
 * @property-read Account|null $parent
 * @method static Builder<static>|Account active()
 * @method static Builder<static>|Account newModelQuery()
 * @method static Builder<static>|Account newQuery()
 * @method static Builder<static>|Account nonGroup()
 * @method static Builder<static>|Account ofType(string $type)
 * @method static Builder<static>|Account onlyTrashed()
 * @method static Builder<static>|Account parents()
 * @method static Builder<static>|Account query()
 * @method static Builder<static>|Account rootAccounts()
 * @method static Builder<static>|Account whereAutomaticPostingsDisabled($value)
 * @method static Builder<static>|Account whereCode($value)
 * @method static Builder<static>|Account whereCreatedAt($value)
 * @method static Builder<static>|Account whereCurrencyCode($value)
 * @method static Builder<static>|Account whereCurrentBalance($value)
 * @method static Builder<static>|Account whereDeletedAt($value)
 * @method static Builder<static>|Account whereDescription($value)
 * @method static Builder<static>|Account whereId($value)
 * @method static Builder<static>|Account whereIsActive($value)
 * @method static Builder<static>|Account whereIsGroup($value)
 * @method static Builder<static>|Account whereIsTaxable($value)
 * @method static Builder<static>|Account whereLastPostedAt($value)
 * @method static Builder<static>|Account whereLevel($value)
 * @method static Builder<static>|Account whereName($value)
 * @method static Builder<static>|Account whereNormalBalance($value)
 * @method static Builder<static>|Account whereOpeningBalance($value)
 * @method static Builder<static>|Account whereOpeningBalanceDate($value)
 * @method static Builder<static>|Account whereOrganizationId($value)
 * @method static Builder<static>|Account whereParentId($value)
 * @method static Builder<static>|Account whereType($value)
 * @method static Builder<static>|Account whereUpdatedAt($value)
 * @method static Builder<static>|Account withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Account withoutTrashed()
 * @mixin \Eloquent
 */
class Account extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'organization_id',
        'parent_id',
        'name',
        'code',
        'description',
        'level',
        'currency_code',
        'opening_balance',
        'current_balance',
        'opening_balance_date',
        'is_taxable',
        'automatic_postings_disabled',
        'type',
        'normal_balance',
        'is_group',
        'is_active',
        'last_posted_at',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'automatic_postings_disabled' => 'boolean',
        'is_group' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'normal_balance' => 'string',
        'level' => 'integer',
        'opening_balance_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    // Relationships with proper return types
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    // Helper Methods
    public function getCurrentBalanceAttribute(): float
    {
        $debitTotal = (float) $this->journalLines()
            ->whereHas('journal', function ($query) {
                $query->where('is_posted', true)
                    ->where('is_reversed', false);
            })
            ->sum('debit');

        $creditTotal = (float) $this->journalLines()
            ->whereHas('journal', function ($query) {
                $query->where('is_posted', true)
                    ->where('is_reversed', false);
            })
            ->sum('credit');

        $openingBalance = (float) ($this->opening_balance ?? 0);

        if ($this->normal_balance === 'Debit') {
            return $openingBalance + $debitTotal - $creditTotal;
        } else {
            return $openingBalance + $creditTotal - $debitTotal;
        }
    }

    public function getOpeningBalanceFormattedAttribute(): string
    {
        // Convert decimal to float for number_format
        $openingBalance = (float) ($this->opening_balance ?? 0);
        return number_format($openingBalance, 2);
    }

    /**
     * Scope a query to only include active accounts
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include accounts of a specific type
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include parent accounts (no parent_id)
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to exclude group accounts
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNonGroup(Builder $query): Builder
    {
        return $query->where('is_group', false);
    }

    /**
     * Scope a query to only include root group accounts
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRootAccounts(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->where('is_group', true);
    }

    // Additional helper methods for common account types
    public function isAsset(): bool
    {
        return $this->type === 'Asset';
    }

    public function isLiability(): bool
    {
        return $this->type === 'Liability';
    }

    public function isEquity(): bool
    {
        return $this->type === 'Equity';
    }

    public function isRevenue(): bool
    {
        return $this->type === 'Revenue';
    }

    public function isExpense(): bool
    {
        return $this->type === 'Expense';
    }

    public function canHaveDebitBalance(): bool
    {
        return $this->normal_balance === 'Debit';
    }

    public function canHaveCreditBalance(): bool
    {
        return $this->normal_balance === 'Credit';
    }

    // Get full account code with parent hierarchy
    public function getFullCodeAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_code . '.' . $this->code;
        }
        return $this->code;
    }

    // Get account hierarchy as string
    public function getHierarchyAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->hierarchy . ' > ' . $this->name;
        }
        return $this->name;
    }

    // Get all descendants recursively
    public function getAllDescendants()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }
        
        return $descendants;
    }

    // Check if account can be deleted (no transactions, no children)
    public function isDeletable(): bool
    {
        return $this->journalLines()->count() === 0 && $this->children()->count() === 0;
    }

    // Get account balance as of specific date
    public function getBalanceAsOf(string $date): float
    {
        $debitTotal = (float) $this->journalLines()
            ->whereHas('journal', function ($query) use ($date) {
                $query->where('is_posted', true)
                    ->where('is_reversed', false)
                    ->where('date', '<=', $date);
            })
            ->sum('debit');

        $creditTotal = (float) $this->journalLines()
            ->whereHas('journal', function ($query) use ($date) {
                $query->where('is_posted', true)
                    ->where('is_reversed', false)
                    ->where('date', '<=', $date);
            })
            ->sum('credit');

        $openingBalance = (float) ($this->opening_balance ?? 0);

        if ($this->normal_balance === 'Debit') {
            return $openingBalance + $debitTotal - $creditTotal;
        }
        
        return $openingBalance + $creditTotal - $debitTotal;
    }
}
<?php

// app/Models/Accounts/JournalLine.php
namespace App\Models\Accounts;

use App\Models\User;
use App\Models\Core\Branch;
use App\Models\Core\Outlet;
use App\Models\Core\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $journal_id
 * @property int $account_id
 * @property int|null $branch_id
 * @property int|null $warehouse_id
 * @property int|null $outlet_id
 * @property numeric $debit
 * @property numeric $credit
 * @property string|null $line_memo
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Accounts\Account $account
 * @property-read Branch|null $branch
 * @property-read User|null $createdBy
 * @property-read float $amount
 * @property-read string $type
 * @property-read \App\Models\Accounts\Journal $journal
 * @property-read Outlet|null $outlet
 * @property-read User|null $updatedBy
 * @property-read Warehouse|null $warehouse
 * @method static Builder<static>|JournalLine creditEntries()
 * @method static Builder<static>|JournalLine debitEntries()
 * @method static Builder<static>|JournalLine forAccount(int $accountId)
 * @method static Builder<static>|JournalLine newModelQuery()
 * @method static Builder<static>|JournalLine newQuery()
 * @method static Builder<static>|JournalLine onlyTrashed()
 * @method static Builder<static>|JournalLine query()
 * @method static Builder<static>|JournalLine whereAccountId($value)
 * @method static Builder<static>|JournalLine whereBranchId($value)
 * @method static Builder<static>|JournalLine whereCreatedAt($value)
 * @method static Builder<static>|JournalLine whereCreatedBy($value)
 * @method static Builder<static>|JournalLine whereCredit($value)
 * @method static Builder<static>|JournalLine whereDebit($value)
 * @method static Builder<static>|JournalLine whereDeletedAt($value)
 * @method static Builder<static>|JournalLine whereId($value)
 * @method static Builder<static>|JournalLine whereJournalId($value)
 * @method static Builder<static>|JournalLine whereLineMemo($value)
 * @method static Builder<static>|JournalLine whereOutletId($value)
 * @method static Builder<static>|JournalLine whereUpdatedAt($value)
 * @method static Builder<static>|JournalLine whereUpdatedBy($value)
 * @method static Builder<static>|JournalLine whereWarehouseId($value)
 * @method static Builder<static>|JournalLine withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|JournalLine withoutTrashed()
 * @mixin \Eloquent
 */
class JournalLine extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_id',
        'account_id',
        'branch_id',
        'warehouse_id',
        'outlet_id',
        'debit',
        'credit',
        'line_memo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'debit' => 'decimal:6',
        'credit' => 'decimal:6',
        'deleted_at' => 'datetime',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getAmountAttribute(): float
    {
        return (float) ($this->debit > 0 ? $this->debit : $this->credit);
    }

    public function getTypeAttribute(): string
    {
        return $this->debit > 0 ? 'Debit' : 'Credit';
    }

    public function scopeDebitEntries(Builder $query): Builder
    {
        return $query->where('debit', '>', 0);
    }

    public function scopeCreditEntries(Builder $query): Builder
    {
        return $query->where('credit', '>', 0);
    }

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }
}
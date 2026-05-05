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
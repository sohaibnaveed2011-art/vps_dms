<?php

namespace App\Models\Account;

use App\Models\Core\Organization;
use App\Models\Voucher\Payment;
use App\Models\Voucher\Receipt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function glTransactions(): HasMany
    {
        return $this->hasMany(GlTransaction::class, 'account_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'account_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'account_id');
    }

    // Scopes
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}

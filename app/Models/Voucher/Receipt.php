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

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

<?php

namespace App\Models\Account;

use App\Models\Core\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gl_transactions';

    protected $fillable = [
        'organization_id',
        'account_id',
        'date',
        'debit',
        'credit',
        'narration',
        'document_number',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Polymorphic reference (Invoice, Payment, Purchase Bill, Receipt, etc.)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeLatestFirst($q)
    {
        return $q->orderBy('date', 'desc')->orderBy('id', 'desc');
    }
}

<?php

namespace App\Models\Voucher;

use App\Models\Account\GlTransaction;
use App\Models\Core\Organization;
use App\Models\Inventory\StockTransaction;
use App\Models\Partner\Supplier;
use App\Traits\HasUserTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNote extends Model
{
    use HasFactory, HasUserTimestamps, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'purchase_bill_id',
        'supplier_id',
        'document_number',
        'date',
        'grand_total',
        'created_by',
        'reviewed_by',
        'approved_by',
        'updated_by',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'grand_total' => 'decimal:4',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'document');
    }

    /**
     * Get the GL entries posted as a result of this Debit Note.
     */
    public function glTransactions(): MorphMany
    {
        return $this->morphMany(GlTransaction::class, 'reference');
    }

    /**
     * Get the stock transactions (inventory OUT) posted as a result of this return.
     */
    public function stockTransactions(): MorphMany
    {
        return $this->morphMany(StockTransaction::class, 'reference');
    }
}

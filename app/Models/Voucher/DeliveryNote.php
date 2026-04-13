<?php

namespace App\Models\Voucher;

use App\Models\User;
use App\Models\Core\Organization;
use App\Models\Inventory\StockTransaction;
use App\Traits\HasUserTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryNote extends Model
{
    use HasFactory, SoftDeletes, HasUserTimestamps;

    protected $fillable = [
        'organization_id',
        'sale_order_id',
        'invoice_id',
        'document_number',
        'date',
        'rider_id',
        'status',
        'created_by',
        'reviewed_by',
        'approved_by',
        'updated_by',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relationships
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function saleOrder(): BelongsTo { return $this->belongsTo(SaleOrder::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }

    public function rider(): BelongsTo { return $this->belongsTo(User::class, 'rider_id'); }
    public function items(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'document');
    }

    /**
     * Get the stock transactions (inventory OUT) confirmed by this Delivery Note.
     */
    public function stockTransactions(): MorphMany
    {
        return $this->morphMany(StockTransaction::class, 'reference');
    }
}

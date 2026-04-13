<?php

namespace App\Models\Voucher;

use App\Models\Core\Organization;
use App\Models\Inventory\StockTransaction;
use App\Models\User;
use App\Traits\HasUserTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptNote extends Model
{
    use HasFactory, SoftDeletes, HasUserTimestamps;

    protected $fillable = [
        'organization_id',
        'purchase_order_id',
        'purchase_bill_id',
        'document_number',
        'date',
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
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function purchaseBill(): BelongsTo { return $this->belongsTo(PurchaseBill::class); }
    
    public function items(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'document');
    }

    public function stockTransactions(): MorphMany // Links to stock updates triggered by this GRN
    {
        return $this->morphMany(StockTransaction::class, 'reference');
    }
}

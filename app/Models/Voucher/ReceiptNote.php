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

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $purchase_order_id
 * @property int|null $purchase_bill_id
 * @property int $voucher_type_id
 * @property string $document_number
 * @property \Illuminate\Support\Carbon $date
 * @property string $status
 * @property int|null $created_by
 * @property int|null $reviewed_by
 * @property int|null $approved_by
 * @property int|null $updated_by
 * @property string|null $reviewed_at
 * @property string|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $approver
 * @property-read User|null $creator
 * @property-read User|null $editor
 * @property-read Organization $organization
 * @property-read \App\Models\Voucher\PurchaseBill|null $purchaseBill
 * @property-read \App\Models\Voucher\PurchaseOrder|null $purchaseOrder
 * @property-read User|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote wherePurchaseBillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptNote withoutTrashed()
 * @mixin \Eloquent
 */
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

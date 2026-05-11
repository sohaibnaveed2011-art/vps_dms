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

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $sale_order_id
 * @property int|null $invoice_id
 * @property int $voucher_type_id
 * @property string $document_number
 * @property \Illuminate\Support\Carbon $date
 * @property int|null $rider_id
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
 * @property-read \App\Models\Voucher\Invoice|null $invoice
 * @property-read Organization $organization
 * @property-read User|null $reviewer
 * @property-read User|null $rider
 * @property-read \App\Models\Voucher\SaleOrder|null $saleOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereRiderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereSaleOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryNote withoutTrashed()
 * @mixin \Eloquent
 */
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

<?php

namespace App\Models\Voucher;

use App\Contracts\VoucherWorkflow;
use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\Core\Outlet;
use App\Models\Core\Warehouse;
use App\Models\Partner\Customer;
use App\Models\User;
use App\Traits\HasUserTimestamps;
use App\Traits\HasVoucherWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $branch_id
 * @property int|null $warehouse_id
 * @property int|null $outlet_id
 * @property int|null $financial_year_id
 * @property int $customer_id
 * @property int $voucher_type_id
 * @property string $document_number
 * @property \Illuminate\Support\Carbon $order_date
 * @property \Illuminate\Support\Carbon|null $delivery_date
 * @property numeric $grand_total
 * @property string $status
 * @property int|null $created_by
 * @property int|null $reviewed_by
 * @property int|null $approved_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $approver
 * @property-read Branch|null $branch
 * @property-read User|null $creator
 * @property-read Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher\DeliveryNote> $deliveryNotes
 * @property-read int|null $delivery_notes_count
 * @property-read User|null $editor
 * @property-read Organization $organization
 * @property-read Outlet|null $outlet
 * @property-read User|null $reviewer
 * @property-read \App\Models\Voucher\VoucherType $voucherType
 * @property-read Warehouse|null $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder visibleTo(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereFinancialYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereOutletId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder withoutTrashed()
 * @mixin \Eloquent
 */
class SaleOrder extends Model implements VoucherWorkflow
{
    use HasFactory, HasUserTimestamps, HasVoucherWorkflow, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'warehouse_id',
        'outlet_id',
        'customer_id',
        'voucher_type_id',
        'document_number',
        'order_date',
        'delivery_date',
        'grand_total',
        'status',

        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',

        'fulfilled_by_level',
        'fulfilled_by_id',
        'allocated_at',
        'fulfilled_at',
    ];

    protected $casts = [
        'order_date'    => 'date',
        'delivery_date' => 'date',
        'grand_total'   => 'decimal:4',
        'reviewed_at'   => 'datetime',
        'approved_at'   => 'datetime',
        'allocated_at'  => 'datetime',
        'fulfilled_at'  => 'datetime',
    ];

    public function getVoucherPermissionPrefix(): string
    {
        return 'voucher.sale_order';
    }

    /* ======================
     | Relationships
     ====================== */

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function outlet(): BelongsTo { return $this->belongsTo(Outlet::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function voucherType(): BelongsTo { return $this->belongsTo(VoucherType::class); }

    public function items(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'document');
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    /* ======================
     | Visibility Scope (FIXED)
     ====================== */

    public function scopeVisibleTo($query, User $user)
    {
        $ctx = $user->activeContext();

        return $query
            ->where('organization_id', $ctx->organization_id)
            ->where(function ($q) use ($ctx, $user) {
                $q->where(function ($scope) use ($ctx) {
                    if ($ctx->outlet_id) {
                        $scope->where('outlet_id', $ctx->outlet_id);
                    } elseif ($ctx->warehouse_id) {
                        $scope->where('warehouse_id', $ctx->warehouse_id);
                    } elseif ($ctx->branch_id) {
                        $scope->where('branch_id', $ctx->branch_id);
                    }
                })
                ->orWhere('created_by', $user->id);
            });
    }
}

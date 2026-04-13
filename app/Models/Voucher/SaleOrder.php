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

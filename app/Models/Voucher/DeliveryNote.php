<?php

namespace App\Models\Vouchers;

use App\Models\User;
use App\Models\Vouchers\Invoice;
use App\Models\Core\Organization;
use App\Models\Vouchers\SaleOrder;
use App\Models\Vouchers\VoucherType;
use App\Models\Vouchers\BaseVoucher;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNote extends BaseVoucher
{
    protected $table = 'delivery_notes';

    protected $fillable = [
        'organization_id',
        'sale_order_id',
        'invoice_id',
        'voucher_type_id',
        'document_number',
        'date',
        'rider_id',
        'status',
        'submitted_at',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'rejection_details',
        'approval_attempts',
        'resubmitted_at',
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

    /* ======================
     |  Relationships
     ====================== */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function voucherType(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    /* ======================
     |  Business Logic
     ====================== */

    /**
     * Mark delivery as picked up
     */
    public function markPicked(?int $userId = null): self
    {
        $this->status = 'picked';
        $this->save();

        $this->changeStatus('picked', 'Order picked up by rider');

        return $this;
    }

    /**
     * Mark delivery as in transit
     */
    public function markInTransit(?int $userId = null): self
    {
        $this->status = 'in_transit';
        $this->save();

        $this->changeStatus('in_transit', 'Order is out for delivery');

        return $this;
    }

    /**
     * Mark delivery as delivered
     */
    public function markDelivered(?int $userId = null): self
    {
        $this->status = 'delivered';
        $this->save();

        $this->changeStatus('delivered', 'Order delivered successfully');

        // If linked to invoice, could trigger invoice status update
        if ($this->invoice_id) {
            // Optionally update invoice status or create receipt
            event(new \App\Events\DeliveryCompleted($this));
        }

        return $this;
    }

    /**
     * Cancel delivery
     */
    public function cancel(string $reason, ?int $userId = null): self
    {
        $this->status = 'cancelled';
        $this->rejection_reason = $reason;
        $this->rejected_by = $userId ?? auth()->id();
        $this->rejected_at = \Carbon\Carbon::now();
        $this->save();

        $this->changeStatus('cancelled', $reason);

        return $this;
    }

    /**
     * Check if delivery can be assigned to rider
     */
    public function canAssignRider(): bool
    {
        return in_array($this->status, ['draft', 'picked']) && is_null($this->rider_id);
    }

    /**
     * Check if delivery is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['picked', 'in_transit']);
    }

    /**
     * Check if delivery is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'delivered';
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopePendingDelivery(Builder $query)
    {
        return $query->whereIn('status', ['draft', 'picked', 'in_transit']);
    }

    public function scopeByRider(Builder $query, int $riderId)
    {
        return $query->where('rider_id', $riderId);
    }

    public function scopeDeliveredBetween(Builder $query, string $startDate, string $endDate)
    {
        return $query->where('status', 'delivered')
            ->whereBetween('date', [$startDate, $endDate]);
    }

    /* ======================
     |  Accessors
     ====================== */

    public function getRiderNameAttribute(): ?string
    {
        return $this->rider?->name;
    }

    public function getIsDeliveredAttribute(): bool
    {
        return $this->status === 'delivered';
    }

    public function getDeliveryDurationAttribute(): ?string
    {
        if (!$this->isCompleted()) {
            return null;
        }

        $createdAt = $this->created_at;
        $deliveredAt = $this->updated_at; // Assuming delivered status update time

        return $createdAt->diffForHumans($deliveredAt, true);
    }
}
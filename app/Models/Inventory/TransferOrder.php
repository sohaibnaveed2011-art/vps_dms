<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $voucher_type_id
 * @property string|null $document_type
 * @property int|null $document_id
 * @property string|null $source_location_type
 * @property int|null $source_location_id
 * @property string|null $destination_location_type
 * @property int|null $destination_location_id
 * @property string $document_number
 * @property string $status
 * @property numeric $total_quantity
 * @property numeric $grand_total_value
 * @property int|null $requested_by
 * @property \Illuminate\Support\Carbon|null $requested_at
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $in_transit_by
 * @property \Illuminate\Support\Carbon|null $in_transit_at
 * @property int|null $completed_by
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int|null $rejected_by
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\User|null $completedBy
 * @property-read Model|\Eloquent|null $destinationLocation
 * @property-read Model|\Eloquent|null $document
 * @property-read \App\Models\User|null $inTransitBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\TransferOrderItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Core\Organization $organization
 * @property-read \App\Models\User|null $requestedBy
 * @property-read Model|\Eloquent|null $sourceLocation
 * @property-read \App\Models\Voucher\VoucherType $voucherType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereCompletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereDestinationLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereDestinationLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereGrandTotalValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereInTransitAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereInTransitBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereRejectedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereRequestedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereSourceLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereSourceLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereTotalQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder whereVoucherTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrder withoutTrashed()
 * @mixin \Eloquent
 */
class TransferOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'voucher_type_id',
        'document_type',
        'document_id',
        'source_location_type',
        'source_location_id',
        'destination_location_type',
        'destination_location_id',
        'document_number',
        'status',
        'total_quantity',
        'grand_total_value',
        'requested_by',
        'requested_at',
        'approved_by',
        'approved_at',
        'in_transit_by',
        'in_transit_at',
        'completed_by',
        'completed_at',
        'rejected_by',
        'rejected_at',
        'created_by',
        'updated_by',
        'notes',
    ];

    protected $casts = [
        'total_quantity'    => 'decimal:6',
        'grand_total_value' => 'decimal:4',
        'requested_at'      => 'datetime',
        'approved_at'       => 'datetime',
        'in_transit_at'     => 'datetime',
        'completed_at'      => 'datetime',
        'rejected_at'       => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(\App\Models\Core\Organization::class);
    }

    public function voucherType()
    {
        return $this->belongsTo(\App\Models\Voucher\VoucherType::class);
    }

    public function document()
    {
        return $this->morphTo();
    }

    public function sourceLocation()
    {
        return $this->morphTo();
    }

    public function destinationLocation()
    {
        return $this->morphTo();
    }

    public function items()
    {
        return $this->hasMany(TransferOrderItem::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function inTransitBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'in_transit_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'completed_by');
    }
}

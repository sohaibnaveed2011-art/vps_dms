<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InventoryReservation extends Model
{
    protected $fillable = [
        'organization_id',
        'stock_location_id',
        'product_variant_id',
        'inventory_batch_id',
        'condition_id',
        'quantity',
        'priority',
        'expires_at',
        'released_at',
        'consumed_at',
        'status',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'released_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function reference()
    {
        return $this->morphTo();
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function location()
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function condition()
    {
        return $this->belongsTo(InventoryCondition::class);
    }

    public function serials()
    {
        return $this->belongsToMany(
            SerialNumber::class,
            'reservation_serials',
            'reservation_id',
            'serial_number_id'
        );
    }
}

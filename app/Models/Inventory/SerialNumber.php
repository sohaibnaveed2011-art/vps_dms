<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialNumber extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'product_variant_id',
        'inventory_batch_id',
        'stock_location_id',
        'condition_id',
        'serial',
        'status',
        'reserved_at',
        'sold_at',
        'returned_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'sold_at'     => 'datetime',
        'returned_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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
}

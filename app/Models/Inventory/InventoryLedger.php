<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InventoryLedger extends Model
{
    protected $table = 'inventory_ledger';

    protected $fillable = [
        'organization_id',
        'stock_location_id',
        'product_variant_id',
        'inventory_batch_id',
        'reference_type',
        'reference_id',
        'transaction_type',
        'quantity_in',
        'quantity_out',
        'unit_cost',
        'total_cost',
        'created_by',
    ];

    protected $casts = [
        'quantity_in' => 'decimal:6',
        'quantity_out' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'total_cost' => 'decimal:6',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function location()
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}

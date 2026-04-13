<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'transfer_order_id',
        'product_variant_id',
        'inventory_batch_id',
        'unit_id',
        'quantity',
        'unit_cost',
        'line_total',
        'allocated_quantity',
        'is_allocated',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity'           => 'decimal:6',
        'unit_cost'          => 'decimal:4',
        'line_total'         => 'decimal:4',
        'allocated_quantity' => 'decimal:6',
        'is_allocated'       => 'boolean',
    ];

    public function transferOrder()
    {
        return $this->belongsTo(TransferOrder::class);
    }

    public function variant()
    {
        return $this->belongsTo(\App\Models\Inventory\ProductVariant::class, 'product_variant_id');
    }

    public function batch()
    {
        return $this->belongsTo(\App\Models\Inventory\InventoryBatch::class, 'inventory_batch_id');
    }
}

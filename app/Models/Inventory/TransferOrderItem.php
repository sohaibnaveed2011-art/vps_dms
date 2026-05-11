<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $transfer_order_id
 * @property int $product_variant_id
 * @property int|null $inventory_batch_id
 * @property int|null $unit_id
 * @property numeric $quantity
 * @property numeric $unit_cost
 * @property numeric $line_total
 * @property numeric $allocated_quantity
 * @property bool $is_allocated
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Inventory\InventoryBatch|null $batch
 * @property-read \App\Models\Inventory\TransferOrder $transferOrder
 * @property-read \App\Models\Inventory\ProductVariant $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereAllocatedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereInventoryBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereIsAllocated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereLineTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereTransferOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereUnitCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferOrderItem withoutTrashed()
 * @mixin \Eloquent
 */
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

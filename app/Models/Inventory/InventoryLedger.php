<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $stock_location_id
 * @property int $product_variant_id
 * @property int|null $inventory_batch_id
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string $transaction_type
 * @property numeric $quantity_in
 * @property numeric $quantity_out
 * @property numeric $unit_cost
 * @property numeric $total_cost
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Inventory\InventoryBatch|null $batch
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Inventory\StockLocation $location
 * @property-read \App\Models\Inventory\ProductVariant $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereInventoryBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereQuantityIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereQuantityOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereStockLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereTotalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereTransactionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereUnitCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

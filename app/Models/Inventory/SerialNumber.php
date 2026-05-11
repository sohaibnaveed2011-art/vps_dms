<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $product_variant_id
 * @property int|null $inventory_batch_id
 * @property int $stock_location_id
 * @property int $condition_id
 * @property string $serial
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $reserved_at
 * @property \Illuminate\Support\Carbon|null $sold_at
 * @property \Illuminate\Support\Carbon|null $returned_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Inventory\InventoryBatch|null $batch
 * @property-read \App\Models\Inventory\InventoryCondition $condition
 * @property-read \App\Models\Inventory\StockLocation $location
 * @property-read \App\Models\Inventory\ProductVariant $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereConditionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereInventoryBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereReservedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereReturnedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereSerial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereSoldAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereStockLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialNumber withoutTrashed()
 * @mixin \Eloquent
 */
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

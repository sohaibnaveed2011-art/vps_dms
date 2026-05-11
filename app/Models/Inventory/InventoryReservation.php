<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $stock_location_id
 * @property int $product_variant_id
 * @property int|null $inventory_batch_id
 * @property int $condition_id
 * @property numeric $quantity
 * @property int $priority
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $released_at
 * @property \Illuminate\Support\Carbon|null $consumed_at
 * @property string $status
 * @property string $reference_type
 * @property int $reference_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Inventory\InventoryBatch|null $batch
 * @property-read \App\Models\Inventory\InventoryCondition $condition
 * @property-read \App\Models\Inventory\StockLocation $location
 * @property-read Model|\Eloquent $reference
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\SerialNumber> $serials
 * @property-read int|null $serials_count
 * @property-read \App\Models\Inventory\ProductVariant $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereConditionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereConsumedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereInventoryBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereReleasedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereStockLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

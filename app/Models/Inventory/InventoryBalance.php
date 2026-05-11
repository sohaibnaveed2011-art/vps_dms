<?php
namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $stock_location_id
 * @property int $product_variant_id
 * @property int|null $inventory_batch_id
 * @property int $condition_id
 * @property float $quantity
 * @property float $reserved_quantity
 * @property float $min_stock
 * @property float $reorder_point
 * @property float $avg_cost
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Inventory\InventoryBatch|null $batch
 * @property-read mixed $available_quantity
 * @property-read Organization $organization
 * @property-read \App\Models\Inventory\StockLocation $stockLocation
 * @property-read \App\Models\Inventory\ProductVariant|null $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereAvgCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereConditionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereInventoryBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereMinStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereReorderPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereReservedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereStockLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBalance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class InventoryBalance extends Model
{
    use HasFactory;

    protected $table = 'inventory_balances';

    protected $fillable = [
        'organization_id',
        'stock_location_id',
        'product_variant_id',
        'inventory_batch_id',
        'condition_id',
        'quantity',
        'reserved_quantity',
        'min_stock',
        'reorder_point',
        'avg_cost',
    ];

    protected $casts = [
        'quantity' => 'float',
        'reserved_quantity' => 'float',
        'min_stock' => 'float',
        'reorder_point' => 'float',
        'avg_cost' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Business Helpers
    |--------------------------------------------------------------------------
    */

    public function getAvailableQuantityAttribute()
    {
        return bcsub($this->quantity, $this->reserved_quantity, 6);
    }
}

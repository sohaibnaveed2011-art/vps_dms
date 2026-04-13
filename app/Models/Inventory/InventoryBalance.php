<?php
namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

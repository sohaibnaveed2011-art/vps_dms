<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $product_variant_id
 * @property string $batch_number
 * @property \Illuminate\Support\Carbon|null $manufacturing_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property numeric $initial_cost
 * @property bool $is_recalled
 * @property string|null $recall_reason
 * @property string|null $storage_condition
 * @property numeric|null $mrp
 * @property int|null $warranty_months
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\InventoryBalance> $balances
 * @property-read int|null $balances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\InventoryLedger> $ledger
 * @property-read int|null $ledger_count
 * @property-read \App\Models\Inventory\ProductVariant $productVariant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereBatchNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereInitialCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereIsRecalled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereManufacturingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereMrp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereRecallReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereStorageCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch whereWarrantyMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryBatch withoutTrashed()
 * @mixin \Eloquent
 */
class InventoryBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_batches';

    protected $fillable = [
        'product_variant_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'initial_cost',
        'mrp',
        'remaining_quantity',
        'warranty_months',
        'is_recalled',
        'recall_reason',
        'storage_condition',
        'status',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'initial_cost' => 'decimal:6',
        'mrp' => 'decimal:6',
        'remaining_quantity' => 'decimal:6',
        'is_recalled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function (InventoryBatch $batch) {

            // Prevent changing cost after stock exists
            if ($batch->isDirty('initial_cost') && $batch->remaining_quantity > 0) {
                throw new \InvalidArgumentException(
                    'Cannot change initial cost after stock movement.'
                );
            }

            // Prevent changing product_variant
            if ($batch->isDirty('product_variant_id')) {
                throw new \InvalidArgumentException(
                    'Cannot change product_variant once batch is created.'
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function balances()
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function ledger()
    {
        return $this->hasMany(InventoryLedger::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Business Helpers
    |--------------------------------------------------------------------------
    */

    public function closeIfEmpty(): void
    {
        if ($this->remaining_quantity <= 0) {
            $this->status = 'closed';
            $this->save();
        }
    }

    public function isExpired(): bool
    {
        return $this->expiry_date
            ? now()->greaterThan($this->expiry_date)
            : false;
    }
}

<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

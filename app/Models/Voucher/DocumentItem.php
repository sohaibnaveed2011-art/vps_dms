<?php

namespace App\Models\Vouchers;

use App\Models\Core\Tax;
use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\ProductVariant;

class DocumentItem extends Model
{
    protected $table = 'document_items';

    protected $fillable = [
        'organization_id',
        'document_type',
        'document_id',
        'product_variant_id',
        'tax_id',
        'inventory_batch_id',
        'cost_of_goods_sold',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_rate' => 'decimal:3',
        'line_total' => 'decimal:4',
        'cost_of_goods_sold' => 'decimal:4',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function document()
    {
        return $this->morphTo();
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function inventoryBatch()
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    /* ======================
     |  Accessors
     ====================== */

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function getTaxAmountAttribute(): float
    {
        return ($this->subtotal - $this->discount_amount) * ($this->tax_rate / 100);
    }
}
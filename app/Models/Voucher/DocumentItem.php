<?php

namespace App\Models\Voucher;

use App\Models\Core\Organization;
use App\Models\Core\Tax;
use App\Models\Inventory\Batch;
use App\Models\Inventory\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentItem extends Model
{
    use HasFactory;

    protected $table = 'document_items';

    protected $fillable = [
        'organization_id',
        'document_type',
        'document_id',
        'product_variant_id',
        'tax_id',
        'batch_id',
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
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:4',
        'cost_of_goods_sold' => 'decimal:4',
    ];

    // Relationships
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function item(): BelongsTo { return $this->belongsTo(ProductVariant::class); }
    public function tax(): BelongsTo { return $this->belongsTo(Tax::class); }
    // public function batch(): BelongsTo { return $this->belongsTo(Batch::class); } // Optional

    public function document(): MorphTo // Links back to Invoice, PO, DN, etc.
    {
        return $this->morphTo();
    }
}

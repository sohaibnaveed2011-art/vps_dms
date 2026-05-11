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

/**
 * @property int $id
 * @property int $organization_id
 * @property string $document_type
 * @property int $document_id
 * @property int $product_variant_id
 * @property int|null $tax_id
 * @property int|null $inventory_batch_id
 * @property numeric|null $cost_of_goods_sold
 * @property numeric $quantity
 * @property numeric $unit_price
 * @property numeric $discount_amount
 * @property numeric $tax_rate
 * @property numeric $line_total
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $document
 * @property-read ProductVariant|null $item
 * @property-read Organization $organization
 * @property-read Tax|null $tax
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereCostOfGoodsSold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereInventoryBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereLineTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

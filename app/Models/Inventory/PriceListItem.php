<?php
namespace App\Models\Inventory;

use App\Models\Inventory\PriceList;
use App\Models\Inventory\ProductVariant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $price_list_id
 * @property int $product_variant_id
 * @property numeric $price
 * @property numeric|null $min_quantity
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property int $priority
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read PriceList $priceList
 * @property-read ProductVariant|null $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem whereMinQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem wherePriceListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceListItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PriceListItem extends Model
{
    protected $fillable = [
        'price_list_id',
        'product_variant_id',
        'price',
        'min_quantity',
        'starts_at',
        'ends_at',
        'priority',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'priority' => 'integer',
    ];

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
    
}

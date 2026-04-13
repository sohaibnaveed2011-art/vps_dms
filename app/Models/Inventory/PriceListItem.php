<?php
namespace App\Models\Inventory;

use App\Models\Inventory\PriceList;
use App\Models\Inventory\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class PriceListItem extends Model
{
    protected $fillable = [
        'price_list_id',
        'product_variant_id',
        'price',
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

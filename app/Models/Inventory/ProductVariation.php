<?php
namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Relations\Pivot; // Change this
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Pivot // Extend Pivot
{
    use SoftDeletes;

    protected $table = 'product_variation';
    
    // Pivot models usually don't need $fillable if used via sync()
    // but keeping it doesn't hurt.
    protected $fillable = [
        'product_id',
        'variation_id',
    ];
}
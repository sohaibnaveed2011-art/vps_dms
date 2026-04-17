<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use App\Models\Core\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'category_id', 'brand_id', 'tax_id',
        'inventory_account_id', 'sale_account_id', 'cogs_account_id',
        'name', 'description', 'valuation_method',
        'has_warranty', 'warranty_months', 'has_variants', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_warranty' => 'boolean',
        'has_variants' => 'boolean',
    ];

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(Variation::class, 'product_variation', 'product_id', 'variation_id')->using(ProductVariation::class)->withTimestamps();
    }
    public function variants(): HasMany { return $this->hasMany(ProductVariant::class); }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function tax(): BelongsTo { return $this->belongsTo(Tax::class); }
    public function images(): MorphMany { return $this->morphMany(ProductImage::class, 'imageable')->orderBy('sort_order'); }
}
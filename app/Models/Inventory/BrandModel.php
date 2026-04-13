<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'brand_models';

    protected $fillable = [
        'organization_id',
        'brand_id',
        'name',
        'slug',
        'series',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot: validation, normalization, uniqueness, slug cleanup.
     */
    protected static function booted()
    {
        static::saving(function (BrandModel $model) {
            // 1. Normalize name
            $model->name = trim($model->name);

            // 2. Handle Slug logic
            if (empty($model->slug)) {
                // Generate slug from name if missing
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            } else {
                // Normalize provided slug
                $model->slug = strtolower(trim($model->slug));
            }
        });
    }

    // ----------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------

    /**
     * Organization owner
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Parent Brand
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Items under this model
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_model_id');
    }
}

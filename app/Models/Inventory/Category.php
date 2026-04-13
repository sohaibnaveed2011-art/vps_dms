<?php

namespace App\Models\Inventory;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'parent_id',
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Normalize slug and enforce same-organization parent on save.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Category $model) {
            if ($model->slug) {
                $model->slug = strtolower(trim($model->slug));
            } else {
                $model->slug = str($model->name)->slug();
            }

            // If parent_id is set, ensure parent belongs to same organization
            if ($model->parent_id) {
                $parent = self::find($model->parent_id);
                if (! $parent) {
                    throw new \Exception('Parent category not found.');
                }
                if ($parent->organization_id !== $model->organization_id) {
                    throw new \Exception('Parent category must belong to the same organization.');
                }
            }
        });
    }

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}

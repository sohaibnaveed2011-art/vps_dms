<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'path',
        'disk',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}

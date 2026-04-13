<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Promotion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionScope extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'promotion_id',
        'scopeable_type',
        'scopeable_id',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function scopeable()
    {
        return $this->morphTo();
    }
}

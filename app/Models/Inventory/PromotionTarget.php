<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Promotion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionTarget extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'promotion_id',
        'targetable_type',
        'targetable_id',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function targetable()
    {
        return $this->morphTo();
    }
}

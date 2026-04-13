<?php

namespace App\Models\Inventory;

use App\Models\Inventory\PromotionScope;
use App\Models\Inventory\PromotionTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'value',
        'priority',
        'stackable',
        'min_order_amount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'is_active',
    ];

    public function scopes()
    {
        return $this->morphMany(PromotionScope::class, 'scopeable');
    }

    public function targets()
    {
        return $this->morphMany(PromotionTarget::class, 'targetable');
    }

    public function isValid()
    {
        return $this->is_active &&
            now()->between($this->start_date, $this->end_date);
    }
}

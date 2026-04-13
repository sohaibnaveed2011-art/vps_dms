<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryCondition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'is_sellable',
        'is_active',
    ];

    protected $casts = [
        'is_sellable' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function originalIsEquivalent($key)
    {
        return parent::originalIsEquivalent($key);
    }
}

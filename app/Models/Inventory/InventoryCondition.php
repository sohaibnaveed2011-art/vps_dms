<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property bool $is_sellable
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition whereIsSellable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCondition withoutTrashed()
 * @mixin \Eloquent
 */
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

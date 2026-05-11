<?php

namespace App\Models\Governance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $from_type
 * @property int|null $from_id
 * @property string $to_type
 * @property int|null $to_id
 * @property bool $allowed
 * @property bool $is_locked
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereAllowed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereFromType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereToType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockFlowPolicy withoutTrashed()
 * @mixin \Eloquent
 */
class StockFlowPolicy extends Model
{
    use SoftDeletes;
    protected $table = 'stock_flow_policies';

    protected $fillable = [
        'organization_id',
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'allowed',
        'description',
        'is_locked',
    ];

    protected $casts = [
        'allowed' => 'boolean',
        'is_locked' => 'boolean',
    ];
}

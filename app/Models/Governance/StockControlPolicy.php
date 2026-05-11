<?php

namespace App\Models\Governance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $key
 * @property array<array-key, mixed>|null $value
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockControlPolicy withoutTrashed()
 * @mixin \Eloquent
 */
class StockControlPolicy extends Model
{
    use SoftDeletes;
    protected $table = 'stock_control_policies';

    protected $fillable = [
        'organization_id',
        'key',
        'value',
        'description',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}

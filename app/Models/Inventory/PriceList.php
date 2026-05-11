<?php


namespace App\Models\Inventory;

use App\Models\Core\Organization;
use App\Models\Inventory\PriceListItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property int $is_default
 * @property string $currency
 * @property int $is_active
 * @property string|null $starts_at
 * @property string|null $ends_at
 * @property int $priority
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PriceListItem> $items
 * @property-read int|null $items_count
 * @property-read Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceList withoutTrashed()
 * @mixin \Eloquent
 */
class PriceList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'currency',
        'is_default',
        'is_active',
        'starts_at',
        'ends_at',
        'priority',
    ];

    public function items()
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}

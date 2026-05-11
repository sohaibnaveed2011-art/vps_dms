<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $locatable_type
 * @property int $locatable_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $code
 * @property string $type
 * @property string|null $path
 * @property int $level
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\InventoryBalance> $balances
 * @property-read int|null $balances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\InventoryLedger> $ledger
 * @property-read int|null $ledger_count
 * @property-read Model|\Eloquent $locatable
 * @property-read \App\Models\Core\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\InventoryReservation> $reservations
 * @property-read int|null $reservations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereLocatableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereLocatableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLocation withoutTrashed()
 * @mixin \Eloquent
 */
class StockLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'locatable_type',
        'locatable_id',
        'name',
        'code',
        'type',
        'path',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (StockLocation $location) {

            $location->name = trim($location->name);

            if ($location->code) {
                $location->code = strtoupper(trim($location->code));
            }

            if (! $location->organization_id) {
                throw new \InvalidArgumentException(
                    'StockLocation must have organization_id.'
                );
            }

            if (! $location->locatable_type || ! $location->locatable_id) {
                throw new \InvalidArgumentException(
                    'StockLocation must be linked to a locatable entity.'
                );
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Core\Organization::class);
    }

    public function locatable()
    {
        return $this->morphTo();
    }

    public function balances()
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function ledger()
    {
        return $this->hasMany(InventoryLedger::class);
    }

    public function reservations()
    {
        return $this->hasMany(InventoryReservation::class);
    }
}

<?php

namespace App\Models\Auth;

use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\Core\Outlet;
use App\Models\Core\Warehouse;
use App\Models\User;
use App\Models\Voucher\CashRegister;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property int $organization_id
 * @property int|null $branch_id
 * @property int|null $warehouse_id
 * @property int|null $outlet_id
 * @property int|null $cash_register_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Branch|null $branch
 * @property-read CashRegister|null $cashRegister
 * @property-read Organization $organization
 * @property-read Outlet|null $outlet
 * @property-read User $user
 * @property-read Warehouse|null $warehouse
 * @method static Builder<static>|UserContext active()
 * @method static Builder<static>|UserContext inactive()
 * @method static Builder<static>|UserContext newModelQuery()
 * @method static Builder<static>|UserContext newQuery()
 * @method static Builder<static>|UserContext query()
 * @method static Builder<static>|UserContext whereBranchId($value)
 * @method static Builder<static>|UserContext whereCashRegisterId($value)
 * @method static Builder<static>|UserContext whereCreatedAt($value)
 * @method static Builder<static>|UserContext whereEndedAt($value)
 * @method static Builder<static>|UserContext whereId($value)
 * @method static Builder<static>|UserContext whereOrganizationId($value)
 * @method static Builder<static>|UserContext whereOutletId($value)
 * @method static Builder<static>|UserContext whereStartedAt($value)
 * @method static Builder<static>|UserContext whereUpdatedAt($value)
 * @method static Builder<static>|UserContext whereUserId($value)
 * @method static Builder<static>|UserContext whereWarehouseId($value)
 * @mixin \Eloquent
 */
class UserContext extends Model
{
    use HasFactory;

    protected $table = 'user_contexts';

    protected $fillable = [
        'user_id',
        'organization_id',
        'branch_id',
        'warehouse_id',
        'outlet_id',
        'cash_register_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'organization_id' => 'integer',
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'outlet_id' => 'integer',
        'cash_register_id' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->whereNotNull('ended_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle
    |--------------------------------------------------------------------------
    */

    public function activate(): self
    {
        DB::transaction(function () {

            // End any currently active context
            static::where('user_id', $this->user_id)
                ->whereNull('ended_at')
                ->update([
                    'ended_at' => now(),
                ]);

            // Activate this context
            $this->fill([
                'started_at' => now(),
                'ended_at' => null,
            ])->save();
        });

        return $this;
    }

    public function end(?Carbon $endedAt = null): self
    {
        $this->ended_at = $endedAt ?? now();
        $this->save();

        return $this;
    }
}

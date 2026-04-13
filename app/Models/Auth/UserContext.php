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

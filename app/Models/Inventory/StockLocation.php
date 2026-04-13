<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'locatable_type',
        'locatable_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

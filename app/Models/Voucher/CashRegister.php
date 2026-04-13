<?php

namespace App\Models\Voucher;

use App\Models\Auth\UserAssignment;
use App\Models\Auth\UserContext;
use App\Models\Core\Organization;
use App\Models\Core\Outlet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'outlet_id',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // Relationships
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function outlet(): BelongsTo { return $this->belongsTo(Outlet::class); }

    public function userContexts(): HasMany
    {
        return $this->hasMany(UserContext::class);
    }

    public function userAssignments(): MorphMany
    {
        return $this->morphMany(UserAssignment::class, 'assignable');
    }

    /**
     * Get all POS sessions run on this physical register.
     */
    public function posSessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }
}

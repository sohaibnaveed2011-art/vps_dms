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

/**
 * @property int $id
 * @property int $organization_id
 * @property string $registerable_type
 * @property int $registerable_id
 * @property string $name
 * @property string $status
 * @property int $is_locked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Organization $organization
 * @property-read Outlet|null $outlet
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher\PosSession> $posSessions
 * @property-read int|null $pos_sessions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserAssignment> $userAssignments
 * @property-read int|null $user_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserContext> $userContexts
 * @property-read int|null $user_contexts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereRegisterableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereRegisterableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashRegister whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

<?php

namespace App\Models\Core;

use App\Contracts\ContextScope;
use App\Models\Auth\UserAssignment;
use App\Models\Auth\UserContext;
use App\Models\Core\Organization;
use App\Models\Core\Outlet;
use App\Models\Core\Warehouse;
use App\Models\Inventory\CouponScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $code
 * @property string|null $email
 * @property string|null $contact_person
 * @property string|null $contact_no
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string $country
 * @property string|null $zip_code
 * @property numeric|null $longitude
 * @property numeric|null $latitude
 * @property bool $is_fbr_active
 * @property string|null $pos_id
 * @property string|null $pos_auth_token
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CouponScope> $couponScopes
 * @property-read int|null $coupon_scopes_count
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Outlet> $outlets
 * @property-read int|null $outlets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserAssignment> $userAssignments
 * @property-read int|null $user_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserContext> $userContexts
 * @property-read int|null $user_contexts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Warehouse> $warehouses
 * @property-read int|null $warehouses_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereContactNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereIsFbrActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch wherePosAuthToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch wherePosId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch withoutTrashed()
 * @mixin \Eloquent
 */
class Branch extends Model implements ContextScope
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'email',
        'contact_person',
        'contact_no',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'longitude',
        'latitude',
        'is_fbr_active',
        'pos_id',
        'pos_auth_token',
        'is_active',
    ];

    protected $casts = [
        'is_fbr_active' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'pos_auth_token',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function warehouses(): HasMany // ADDED: Warehouses that belong to this branch (branch_id is nullable on warehouse)
    {
        return $this->hasMany(Warehouse::class);
    }

    public function outlets(): HasMany // ADDED: Outlets that belong to this branch (branch_id is nullable on outlet)
    {
        return $this->hasMany(Outlet::class);
    }

    public function userContexts(): HasMany
    {
        return $this->hasMany(UserContext::class);
    }

    public function userAssignments(): HasMany
    {
        // Polymorphic relationship using HasMany and where clauses
        return $this->hasMany(UserAssignment::class, 'assignable_id')
            ->where('assignable_type', self::class);
    }

    // Context Scope

    public function organizationId(): int
    {
        return (int) $this->organization_id;
    }

    public function branchId(): ?int
    {
        return $this->id;
    }

    public function warehouseId(): ?int
    {
        return null;
    }

    public function outletId(): ?int
    {
        return null;
    }

    public function couponScopes(): MorphMany
    {
        return $this->morphMany(CouponScope::class, 'Scopeable');
    }
}

<?php

namespace App\Models\Core;

use App\Contracts\ContextScope;
use App\Models\Auth\UserAssignment;
use App\Models\Auth\UserContext;
use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\Core\Outlet;
use App\Models\Core\WarehouseSection;
use App\Models\Inventory\CouponScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $branch_id
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
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Branch|null $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CouponScope> $couponScopes
 * @property-read int|null $coupon_scopes_count
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Outlet> $outlets
 * @property-read int|null $outlets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WarehouseSection> $sections
 * @property-read int|null $sections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserAssignment> $userAssignments
 * @property-read int|null $user_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserContext> $userContexts
 * @property-read int|null $user_contexts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereContactNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse withoutTrashed()
 * @mixin \Eloquent
 */
class Warehouse extends Model implements ContextScope
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'branch_id',
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
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude'  => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function userContexts(): HasMany
    {
        return $this->hasMany(UserContext::class);
    }

    public function userAssignments(): MorphMany
    {
        return $this->morphMany(UserAssignment::class, 'assignable');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(WarehouseSection::class);
    }

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    // Context Scope

    public function organizationId(): int
    {
        return (int) $this->organization_id;
    }

    public function branchId(): ?int
    {
        return $this->branch_id;
    }

    public function warehouseId(): ?int
    {
        return $this->id;
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

<?php

namespace App\Models\Core;

use App\Models\Auth\UserAssignment;
use App\Models\Auth\UserContext;
use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\Core\OutletSection;
use App\Models\Core\Warehouse;
use App\Models\Inventory\CouponScope;
use App\Models\Voucher\CashRegister;
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
 * @property int|null $warehouse_id
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CashRegister> $cashRegisters
 * @property-read int|null $cash_registers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CouponScope> $couponScopes
 * @property-read int|null $coupon_scopes_count
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OutletSection> $sections
 * @property-read int|null $sections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserAssignment> $userAssignments
 * @property-read int|null $user_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserContext> $userContexts
 * @property-read int|null $user_contexts_count
 * @property-read Warehouse|null $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereContactNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Outlet withoutTrashed()
 * @mixin \Eloquent
 */
class Outlet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'warehouse_id',
        'parent_section_id',
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
        'latitude'  => 'decimal:7',
        'longitude' => 'decimal:7',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
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
        return $this->hasMany(OutletSection::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
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
        return $this->warehouse_id;
    }

    public function outletId(): ?int
    {
        return $this->id;
    }
    public function couponScopes(): MorphMany
    {
        return $this->morphMany(CouponScope::class, 'Scopeable');
    }
}

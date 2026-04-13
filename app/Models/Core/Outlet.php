<?php

namespace App\Models\Core;

use App\Models\Auth\UserAssignment;
use App\Models\Auth\UserContext;
use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\Core\OutletSection;
use App\Models\Core\Warehouse;
use App\Models\Voucher\CashRegister;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}

<?php

namespace App\Models\Core;

use App\Contracts\ContextScope;
use App\Models\Auth\UserAssignment;
use App\Models\Auth\UserContext;
use App\Models\Core\Organization;
use App\Models\Core\Outlet;
use App\Models\Core\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}

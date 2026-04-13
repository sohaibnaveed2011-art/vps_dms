<?php

namespace App\Models;

use App\Models\Auth\UserAssignment;
use App\Models\Auth\UserContext;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens,  Notifiable;

    protected $guard_name = 'web';

    /**
     * Cached active context per request lifecycle.
     */
    protected ?UserContext $cachedContext = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_admin',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function userContexts()
    {
        return $this->hasMany(UserContext::class);
    }

    public function activeUserContext()
    {
        return $this->hasOne(UserContext::class)
            ->whereNull('ended_at');
    }

    public function userAssignments()
    {
        return $this->hasMany(UserAssignment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Active Context Resolution
    |--------------------------------------------------------------------------
    */

    public function activeContext(): ?UserContext
    {
        if ($this->cachedContext !== null) {
            return $this->cachedContext;
        }

        $this->cachedContext = $this->activeUserContext()
            ->with([
                'organization',
                'branch',
                'warehouse',
                'outlet',
                'cashRegister',
            ])
            ->first();

        return $this->cachedContext;
    }

    public function refreshActiveContext(): void
    {
        $this->cachedContext = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Context Shortcuts (Safe Accessors)
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->activeContext()?->organization;
    }

    public function branch()
    {
        return $this->activeContext()?->branch;
    }

    public function warehouse()
    {
        return $this->activeContext()?->warehouse;
    }

    public function outlet()
    {
        return $this->activeContext()?->outlet;
    }

    public function cashRegister()
    {
        return $this->activeContext()?->cashRegister;
    }

    public function organizationId(): ?int
    {
        return $this->activeContext()?->organization_id;
    }

    public function branchId(): ?int
    {
        return $this->activeContext()?->branch_id;
    }

    public function warehouseId(): ?int
    {
        return $this->activeContext()?->warehouse_id;
    }

    public function outletId(): ?int
    {
        return $this->activeContext()?->outlet_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('is_admin', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }
}

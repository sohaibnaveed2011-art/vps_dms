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

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_active
 * @property bool $is_admin
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $profile_photo_path
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read UserContext|null $activeUserContext
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserAssignment> $userAssignments
 * @property-read int|null $user_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserContext> $userContexts
 * @property-read int|null $user_contexts_count
 * @method static Builder<static>|User active()
 * @method static Builder<static>|User admin()
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereIsActive($value)
 * @method static Builder<static>|User whereIsAdmin($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereProfilePhotoPath($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

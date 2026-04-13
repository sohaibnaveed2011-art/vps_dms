<?php

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserAssignment extends Model
{
    use HasFactory;

    protected $table = 'user_assignments';

    protected $fillable = [        
        'user_id',
        'assignable_type',
        'assignable_id',
        'role_id',
        'assigned_by',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'assignable_id' => 'integer',
        'role_id' => 'integer',
        'assigned_by' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function role(): BelongsTo
    {
        $roleModel = config('permission.models.role')
            ?? \Spatie\Permission\Models\Role::class;

        return $this->belongsTo($roleModel, 'role_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeInactive($query)
    {
        return $query->whereNotNull('ended_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle
    |--------------------------------------------------------------------------
    */

    public function activate(): self
    {
        DB::transaction(function () {

            // Deactivate existing active assignment for same scope
            static::where('user_id', $this->user_id)
                ->where('role_id', $this->role_id)
                ->where('assignable_type', $this->assignable_type)
                ->where('assignable_id', $this->assignable_id)
                ->whereNull('ended_at')
                ->update([
                    'ended_at' => now(),
                ]);

            $this->fill([
                'started_at' => $this->started_at ?? now(),
                'ended_at' => null,
            ])->save();
        });

        return $this;
    }

    public function deactivate(?Carbon $endedAt = null): self
    {
        $this->ended_at = $endedAt ?? now();
        $this->save();

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }
}

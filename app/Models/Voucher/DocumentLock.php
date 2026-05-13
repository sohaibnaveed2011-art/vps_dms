<?php

namespace App\Models\Vouchers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

class DocumentLock extends Model
{
    protected $table = 'document_locks';

    protected $fillable = [
        'document_type',
        'document_id',
        'locked_by',
        'locked_until',
        'reason',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function document()
    {
        return $this->morphTo();
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeActive(Builder $query)
    {
        return $query->where('locked_until', '>', now());
    }

    public function scopeExpired(Builder $query)
    {
        return $query->where('locked_until', '<=', now());
    }

    /* ======================
     |  Helper Methods
     ====================== */

    public function isExpired(): bool
    {
        return $this->locked_until <= now();
    }

    public function isLockedBy(User $user): bool
    {
        return $this->locked_by === $user->id;
    }

    public function extend(int $minutes = 30): self
    {
        $this->locked_until = \Carbon\Carbon::now()->addMinutes($minutes);
        $this->save();

        return $this;
    }

    public static function lock(Model $document, User $user, int $minutes = 30, ?string $reason = null): self
    {
        // Remove existing locks
        self::where('document_type', get_class($document))
            ->where('document_id', $document->id)
            ->delete();

        return self::create([
            'document_type' => get_class($document),
            'document_id' => $document->id,
            'locked_by' => $user->id,
            'locked_until' => now()->addMinutes($minutes),
            'reason' => $reason,
        ]);
    }

    public static function unlock(Model $document): void
    {
        self::where('document_type', get_class($document))
            ->where('document_id', $document->id)
            ->delete();
    }

    public static function isLocked(Model $document): bool
    {
        return self::where('document_type', get_class($document))
            ->where('document_id', $document->id)
            ->where('locked_until', '>', now())
            ->exists();
    }

    public static function getLock(Model $document): ?self
    {
        return self::where('document_type', get_class($document))
            ->where('document_id', $document->id)
            ->where('locked_until', '>', now())
            ->first();
    }
}
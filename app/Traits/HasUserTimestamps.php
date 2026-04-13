<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasUserTimestamps
{
    protected static function bootHasUserTimestamps()
    {
        static::creating(function ($model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    /* ======================
     |  Relationships
     ====================== */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* ======================
     |  Workflow Actions
     ====================== */

    public function markReviewed(?int $userId = null): self
    {
        $this->reviewed_by = $userId ?? Auth::id();
        $this->reviewed_at = now();

        return $this;
    }

    public function markApproved(?int $userId = null): self
    {
        $this->approved_by = $userId ?? Auth::id();
        $this->approved_at = now();

        return $this;
    }

    public function clearReview(): self
    {
        $this->reviewed_by = null;
        $this->reviewed_at = null;

        return $this;
    }

    public function clearApproval(): self
    {
        $this->approved_by = null;
        $this->approved_at = null;

        return $this;
    }
}

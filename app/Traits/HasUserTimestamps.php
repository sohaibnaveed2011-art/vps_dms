<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasUserTimestamps
{
    protected static function bootHasUserTimestamps()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
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

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /* ======================
     |  Workflow Actions
     ====================== */

    public function markReviewed(?int $userId = null): self
    {
        $this->reviewed_by = $userId ?? Auth::id();
        $this->reviewed_at = now();
        
        if ($this->canTransitionTo('reviewed')) {
            $this->status = 'reviewed';
        }
        
        $this->save();

        return $this;
    }

    public function markApproved(?int $userId = null): self
    {
        $this->approved_by = $userId ?? Auth::id();
        $this->approved_at = now();
        
        if ($this->canTransitionTo('approved')) {
            $this->status = 'approved';
        }
        
        $this->save();

        return $this;
    }

    public function markSubmitted(?int $userId = null): self
    {
        $this->submitted_at = now();
        
        if ($this->canTransitionTo('submitted')) {
            $this->status = 'submitted';
            $this->approval_attempts++;
        }
        
        $this->save();

        return $this;
    }

    public function markRejected(string $reason, ?array $details = null, ?int $userId = null): self
    {
        $this->rejected_by = $userId ?? Auth::id();
        $this->rejected_at = now();
        $this->rejection_reason = $reason;
        $this->rejection_details = $details;
        
        if ($this->canTransitionTo('rejected')) {
            $this->status = 'rejected';
        }
        
        $this->save();

        // Create rejection history record
        $this->rejectionHistory()->create([
            'attempt_number' => $this->approval_attempts + 1,
            'rejected_by' => $this->rejected_by,
            'reason' => $reason,
            'validation_errors' => $details,
            'rejected_at' => now(),
        ]);

        return $this;
    }

    public function markResubmitted(?int $userId = null): self
    {
        $this->resubmitted_at = now();
        $this->rejected_by = null;
        $this->rejected_at = null;
        $this->rejection_reason = null;
        $this->rejection_details = null;
        
        if ($this->canTransitionTo('draft')) {
            $this->status = 'draft';
        }
        
        $this->save();

        return $this;
    }

    public function clearReview(): self
    {
        $this->reviewed_by = null;
        $this->reviewed_at = null;
        
        if ($this->canTransitionTo('submitted')) {
            $this->status = 'submitted';
        }
        
        $this->save();

        return $this;
    }

    public function clearApproval(): self
    {
        $this->approved_by = null;
        $this->approved_at = null;
        
        if ($this->canTransitionTo('reviewed')) {
            $this->status = 'reviewed';
        }
        
        $this->save();

        return $this;
    }
}
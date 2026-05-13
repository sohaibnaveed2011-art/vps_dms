<?php

namespace App\Traits;

use App\Models\Vouchers\DocumentStatusHistory;
use App\Models\Vouchers\DocumentRejectionHistory;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDocumentWorkflow
{
    use HasUserTimestamps;

    /* ======================
     |  Relationships
     ====================== */

    public function statusHistory(): MorphMany
    {
        return $this->morphMany(DocumentStatusHistory::class, 'document');
    }

    public function rejectionHistory(): MorphMany
    {
        return $this->morphMany(DocumentRejectionHistory::class, 'document');
    }

    /* ======================
     |  Workflow Methods
     ====================== */

    public function changeStatus(string $newStatus, ?string $reason = null, ?array $metadata = null): self
    {
        $oldStatus = $this->status;

        $this->status = $newStatus;
        $this->save();

        $this->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
            'metadata' => $metadata,
            'user_id' => auth()->id(),
        ]);

        return $this;
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = $this->getAllowedTransitions();

        return in_array($newStatus, $allowedTransitions[$this->status] ?? []);
    }

    protected function getAllowedTransitions(): array
    {
        return [
            'draft' => ['submitted', 'cancelled'],
            'submitted' => ['draft', 'reviewed', 'rejected', 'cancelled'],
            'reviewed' => ['approved', 'rejected', 'draft'],
            'approved' => ['posted', 'cancelled'],
            'posted' => ['cancelled'],
            'rejected' => ['draft', 'cancelled'],
            'cancelled' => [],
        ];
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function isDeletable(): bool
    {
        return in_array($this->status, ['draft', 'rejected', 'cancelled']);
    }

    public function isReviewable(): bool
    {
        return $this->status === 'submitted' && is_null($this->reviewed_at);
    }

    public function isApprovable(): bool
    {
        return $this->status === 'reviewed' && is_null($this->approved_at);
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeByStatus(Builder $query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeNotCancelled(Builder $query)
    {
        return $query->where('status', '!=', 'cancelled');
    }

    public function scopeNeedReview(Builder $query)
    {
        return $query->where('status', 'submitted')
            ->whereNull('reviewed_at');
    }

    public function scopeNeedApproval(Builder $query)
    {
        return $query->where('status', 'reviewed')
            ->whereNull('approved_at');
    }

    public function scopeRejected(Builder $query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeApproved(Builder $query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePosted(Builder $query)
    {
        return $query->where('status', 'posted');
    }
}
<?php

namespace App\Models\Vouchers;

use App\Traits\HasAccountingPeriod;
use App\Traits\HasDocumentWorkflow;
use App\Models\Vouchers\DocumentItem;
use App\Models\Vouchers\DocumentLink;
use App\Models\Vouchers\DocumentLock;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vouchers\DocumentComment;
use App\Models\Vouchers\PaymentReference;
use App\Models\Vouchers\DocumentAuditLog;
use App\Models\Vouchers\ReceiptReference;
use App\Models\Vouchers\DocumentAttachment;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Database\Eloquent\Builder;

abstract class BaseVoucher extends Model
{
    use SoftDeletes, HasDocumentWorkflow, HasAccountingPeriod;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'date' => 'date',
        'order_date' => 'date',
        'delivery_date' => 'date',
        'expected_receipt_date' => 'date',
        'submitted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'resubmitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejection_details' => 'array',
        'grand_total' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function items()
    {
        return $this->morphMany(DocumentItem::class, 'document');
    }

    public function attachments()
    {
        return $this->morphMany(DocumentAttachment::class, 'document');
    }

    public function comments()
    {
        return $this->morphMany(DocumentComment::class, 'document');
    }

    public function auditLogs()
    {
        return $this->morphMany(DocumentAuditLog::class, 'document');
    }

    public function sourceLinks()
    {
        return $this->morphMany(DocumentLink::class, 'source_document');
    }

    public function targetLinks()
    {
        return $this->morphMany(DocumentLink::class, 'target_document');
    }

    public function documentLock()
    {
        return $this->morphOne(DocumentLock::class, 'document');
    }

    // Polymorphic references for receipts/payments
    public function receiptReferences()
    {
        return $this->morphMany(ReceiptReference::class, 'reference');
    }

    public function paymentReferences()
    {
        return $this->morphMany(PaymentReference::class, 'reference');
    }

    /* ======================
     |  Helper Methods
     ====================== */

    public function logEvent(string $event, ?array $oldValues = null, ?array $newValues = null): DocumentAuditLog
    {
        return DocumentAuditLog::log($this, $event, $oldValues, $newValues);
    }

    public function addComment(string $comment, bool $isInternal = false, ?array $attachments = null): DocumentComment
    {
        return $this->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $comment,
            'is_internal' => $isInternal,
            'attachments' => $attachments,
        ]);
    }

    public function addAttachment(string $fileName, string $filePath, string $mimeType, int $fileSize, string $hash): DocumentAttachment
    {
        return $this->attachments()->create([
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'hash' => $hash,
            'uploaded_by' => auth()->id(),
        ]);
    }

    public function linkTo(Model $target, string $linkType, ?array $metadata = null): DocumentLink
    {
        return DocumentLink::createLink($this, $target, $linkType, $metadata);
    }

    public function isLocked(): bool
    {
        return DocumentLock::isLocked($this);
    }

    public function getDocumentLock(): ?DocumentLock
    {
        return DocumentLock::getLock($this);
    }

    public function applyLock(int $minutes = 30, ?string $reason = null): DocumentLock
    {
        return DocumentLock::lock($this, auth()->user(), $minutes, $reason);
    }

    public function releaseLock(): void
    {
        DocumentLock::unlock($this);
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeWithLock(Builder $query)
    {
        return $query->with('documentLock');
    }

    public function scopeLocked(Builder $query)
    {
        return $query->whereHas('documentLock', function ($q) {
            $q->where('locked_until', '>', now());
        });
    }

    public function scopeUnlocked(Builder $query)
    {
        return $query->whereDoesntHave('documentLock', function ($q) {
            $q->where('locked_until', '>', now());
        });
    }
}
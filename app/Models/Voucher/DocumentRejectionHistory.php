<?php

namespace App\Models\Vouchers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

class DocumentRejectionHistory extends Model
{
    protected $table = 'document_rejection_history';

    protected $fillable = [
        'document_type',
        'document_id',
        'attempt_number',
        'rejected_by',
        'reason',
        'validation_errors',
        'rejected_at',
    ];

    protected $casts = [
        'validation_errors' => 'array',
        'rejected_at' => 'datetime',
        'attempt_number' => 'integer',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function document()
    {
        return $this->morphTo();
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeForDocument(Builder $query, Model $document)
    {
        return $query->where('document_type', get_class($document))
            ->where('document_id', $document->id);
    }

    public function scopeLatestAttempt(Builder $query)
    {
        return $query->orderBy('attempt_number', 'desc');
    }

    /* ======================
     |  Accessors
     ====================== */

    public function getValidationErrorsSummaryAttribute(): string
    {
        if (!$this->validation_errors) {
            return '';
        }

        $errors = [];
        foreach ($this->validation_errors as $field => $error) {
            $errors[] = "{$field}: " . (is_array($error) ? implode(', ', $error) : $error);
        }

        return implode('; ', $errors);
    }
}
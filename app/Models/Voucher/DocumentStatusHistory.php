<?php

namespace App\Models\Vouchers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

class DocumentStatusHistory extends Model
{
    protected $table = 'document_status_history';

    protected $fillable = [
        'document_type',
        'document_id',
        'from_status',
        'to_status',
        'reason',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function document()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeForDocument(Builder $query, Model $document)
    {
        return $query->where('document_type', get_class($document))
            ->where('document_id', $document->id);
    }

    public function scopeRecent(Builder $query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /* ======================
     |  Accessors
     ====================== */

    public function getTransitionDescriptionAttribute(): string
    {
        if (!$this->from_status) {
            return "Document created with status: {$this->to_status}";
        }

        return "Status changed from {$this->from_status} to {$this->to_status}" . 
               ($this->reason ? " - Reason: {$this->reason}" : "");
    }
}
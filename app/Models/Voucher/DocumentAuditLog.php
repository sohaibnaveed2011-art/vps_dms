<?php

namespace App\Models\Vouchers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

class DocumentAuditLog extends Model
{
    protected $table = 'document_audit_logs';

    protected $fillable = [
        'document_type',
        'document_id',
        'event',
        'old_values',
        'new_values',
        'diff',
        'ip_address',
        'user_agent',
        'user_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'diff' => 'array',
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

    public function scopeByEvent(Builder $query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForDocument(Builder $query, Model $document)
    {
        return $query->where('document_type', get_class($document))
            ->where('document_id', $document->id);
    }

    /* ======================
     |  Helper Methods
     ====================== */

    public static function log(Model $document, string $event, ?array $oldValues = null, ?array $newValues = null): self
    {
        $diff = [];
        if ($oldValues && $newValues) {
            foreach ($newValues as $key => $value) {
                if (isset($oldValues[$key]) && $oldValues[$key] != $value) {
                    $diff[$key] = [
                        'old' => $oldValues[$key],
                        'new' => $value,
                    ];
                }
            }
        }

        return self::create([
            'document_type' => get_class($document),
            'document_id' => $document->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'diff' => $diff,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);
    }
}
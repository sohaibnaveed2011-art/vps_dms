<?php

namespace App\Models\Vouchers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

class DocumentLink extends Model
{
    protected $table = 'document_links';

    protected $fillable = [
        'source_document_type',
        'source_document_id',
        'target_document_type',
        'target_document_id',
        'link_type',
        'link_metadata',
        'created_by',
    ];

    protected $casts = [
        'link_metadata' => 'array',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function sourceDocument()
    {
        return $this->morphTo('source_document', 'source_document_type', 'source_document_id');
    }

    public function targetDocument()
    {
        return $this->morphTo('target_document', 'target_document_type', 'target_document_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeByLinkType(Builder $query, string $linkType)
    {
        return $query->where('link_type', $linkType);
    }

    public function scopeFromDocument(Builder $query, Model $document)
    {
        return $query->where('source_document_type', get_class($document))
            ->where('source_document_id', $document->id);
    }

    public function scopeToDocument(Builder $query, Model $document)
    {
        return $query->where('target_document_type', get_class($document))
            ->where('target_document_id', $document->id);
    }

    /* ======================
     |  Helper Methods
     ====================== */

    public static function createLink(Model $source, Model $target, string $linkType, ?array $metadata = null): self
    {
        return self::create([
            'source_document_type' => get_class($source),
            'source_document_id' => $source->id,
            'target_document_type' => get_class($target),
            'target_document_id' => $target->id,
            'link_type' => $linkType,
            'link_metadata' => $metadata,
            'created_by' => auth()->id(),
        ]);
    }
}
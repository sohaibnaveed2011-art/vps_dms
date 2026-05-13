<?php

namespace App\Models\Vouchers;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentAttachment extends Model
{
    use SoftDeletes;

    protected $table = 'document_attachments';

    protected $fillable = [
        'document_type',
        'document_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'hash',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function document()
    {
        return $this->morphTo();
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /* ======================
     |  Accessors
     ====================== */

    public function getFileSizeForHumansAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeByHash(Builder $query, string $hash)
    {
        return $query->where('hash', $hash);
    }

    public function scopeImageOnly(Builder $query)
    {
        return $query->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function scopeDocumentOnly(Builder $query)
    {
        return $query->whereIn('mime_type', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }
}
<?php

namespace App\Models\Vouchers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Database\Eloquent\Builder;

class DocumentComment extends Model
{
    use SoftDeletes;

    protected $table = 'document_comments';

    protected $fillable = [
        'document_type',
        'document_id',
        'user_id',
        'comment',
        'is_internal',
        'attachments',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'attachments' => 'array',
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

    public function scopeInternal(Builder $query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeExternal(Builder $query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeRecent(Builder $query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
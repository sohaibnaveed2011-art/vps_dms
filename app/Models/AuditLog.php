<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'description',
        'auditable_type',
        'auditable_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}

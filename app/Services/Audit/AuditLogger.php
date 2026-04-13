<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function log(
        ?int $userId,
        string $event,
        ?Model $target = null,
        array $meta = []
    ): void {

        AuditLog::create([
            'user_id' => $userId,
            'event_type' => $event,
            'auditable_type' => $target?->getMorphClass(),
            'auditable_id' => $target?->getKey(),
            'meta' => $meta,
        ]);
    }
}

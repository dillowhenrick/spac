<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public static function log(
        AuditEvent $event,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = []
    ): void {
        $user = auth()->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'user_role' => $user?->memberships()->where('is_primary', true)->value('role'),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'event' => $event,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

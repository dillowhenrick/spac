<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuditAuthListener
{
    public function handleLogin(Login $event): void
    {
        AuditLog::create([
            'user_id' => $event->user->id,
            'user_role' => $event->user->memberships()->where('is_primary', true)->value('role'),
            'auditable_type' => 'user',
            'auditable_id' => $event->user->id,
            'event' => AuditEvent::Login,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        AuditLog::create([
            'user_id' => $event->user->id,
            'user_role' => $event->user->memberships()->where('is_primary', true)->value('role'),
            'auditable_type' => 'user',
            'auditable_id' => $event->user->id,
            'event' => AuditEvent::Logout,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        AuditLog::create([
            'user_id' => $event->user?->id,
            'auditable_type' => $event->user ? 'user' : null,
            'auditable_id' => $event->user?->id,
            'event' => AuditEvent::LoginFailed,
            'new_values' => ['credentials' => ['email' => $event->credentials['email'] ?? null]],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

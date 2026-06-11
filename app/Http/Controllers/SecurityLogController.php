<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;

class SecurityLogController extends Controller
{
    public function index(): Response
    {
        $logs = auth()->user()
            ->securityLogs()
            ->limit(100)
            ->get();

        return Inertia::render('security-log/index', [
            'logs' => $logs->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event->value,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->toISOString(),
            ]),
        ]);
    }
}

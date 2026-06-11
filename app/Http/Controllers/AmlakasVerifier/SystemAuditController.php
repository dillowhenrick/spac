<?php

namespace App\Http\Controllers\AmlakasVerifier;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Request as AppRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemAuditController extends Controller
{
    public function index(Request $httpRequest): Response
    {
        $this->authorize('inspectAny', AppRequest::class);

        $logs = AuditLog::with('user')
            ->latest()
            ->limit(200)
            ->get();

        return Inertia::render('audit/index', [
            'logs' => $logs->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event->value,
                'user' => $log->user?->name,
                'user_role' => $log->user_role,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toISOString(),
            ]),
        ]);
    }

    public function request(AppRequest $request): Response
    {
        $this->authorize('inspect', $request);

        $logs = $request->auditLogs()->with('user')->get();

        return Inertia::render('audit/request', [
            'request' => [
                'id' => $request->id,
                'reference_number' => $request->reference_number,
            ],
            'logs' => $logs->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event->value,
                'user' => $log->user?->name,
                'user_role' => $log->user_role,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toISOString(),
            ]),
        ]);
    }
}

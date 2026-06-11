<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\Request as AppRequest;
use App\Models\RequestAssignment;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $roles = $user->memberships()->pluck('role')->map(fn ($r) => $r->value)->all();

        $stats = [];

        if (in_array(Role::Requester->value, $roles)) {
            $base = AppRequest::where('requester_id', $user->id);

            $stats['requester'] = [
                'total' => (clone $base)->count(),
                'submitted' => (clone $base)->where('status', RequestStatus::Submitted)->count(),
                'in_progress' => (clone $base)->whereIn('status', [
                    RequestStatus::UnderVerification->value,
                    RequestStatus::Verified->value,
                    RequestStatus::Routed->value,
                    RequestStatus::Assigned->value,
                    RequestStatus::InProgress->value,
                    RequestStatus::PendingManagerReview->value,
                ])->count(),
                'released' => (clone $base)->where('status', RequestStatus::Released)->count(),
                'recent' => (clone $base)
                    ->with('targetInstitution')
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (AppRequest $r) => [
                        'id' => $r->id,
                        'reference_number' => $r->reference_number,
                        'request_type' => ['value' => $r->legal_process->value, 'label' => $r->legal_process->label(), 'description' => $r->legal_process->description()],
                        'status' => $r->status->value,
                        'target_institution' => $r->targetInstitution->name,
                        'submitted_at' => $r->submitted_at?->toISOString(),
                    ]),
            ];
        }

        if (in_array(Role::AmlakasVerifier->value, $roles)) {
            $pendingStatuses = [RequestStatus::Submitted->value, RequestStatus::UnderVerification->value];

            $stats['verifier'] = [
                'pending' => AppRequest::whereIn('status', $pendingStatuses)->count(),
                'verified_this_month' => AppRequest::where('status', RequestStatus::Verified)
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)
                    ->count(),
                'overdue_returns' => AppRequest::whereNotNull('request_due_at')
                    ->where('request_due_at', '<', now())
                    ->whereNotIn('status', [RequestStatus::Released->value, RequestStatus::Closed->value])
                    ->count(),
                'recent' => AppRequest::whereIn('status', $pendingStatuses)
                    ->with(['agency', 'requester', 'targetInstitution'])
                    ->oldest('submitted_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (AppRequest $r) => [
                        'id' => $r->id,
                        'reference_number' => $r->reference_number,
                        'request_type' => ['value' => $r->legal_process->value, 'label' => $r->legal_process->label(), 'description' => $r->legal_process->description()],
                        'status' => $r->status->value,
                        'agency' => $r->agency->name,
                        'requester' => $r->requester->name,
                        'target_institution' => $r->targetInstitution->name,
                        'submitted_at' => $r->submitted_at?->toISOString(),
                    ]),
            ];
        }

        if (in_array(Role::InstitutionManager->value, $roles)) {
            $institutionId = $user->institutionMemberships()
                ->where('role', Role::InstitutionManager->value)
                ->value('organization_id');

            $base = AppRequest::where('target_institution_id', $institutionId);
            $activeStatuses = [
                RequestStatus::Routed->value,
                RequestStatus::Assigned->value,
                RequestStatus::InProgress->value,
            ];

            $stats['manager'] = [
                'queue' => (clone $base)->whereIn('status', $activeStatuses)->count(),
                'pending_review' => (clone $base)->where('status', RequestStatus::PendingManagerReview)->count(),
                'overdue_returns' => (clone $base)->whereNotNull('request_due_at')
                    ->where('request_due_at', '<', now())
                    ->whereNotIn('status', [RequestStatus::Released->value, RequestStatus::Closed->value])
                    ->count(),
                'recent' => (clone $base)
                    ->whereIn('status', array_merge($activeStatuses, [RequestStatus::PendingManagerReview->value]))
                    ->with('agency')
                    ->orderByRaw('CASE WHEN request_due_at IS NOT NULL AND request_due_at < CURRENT_TIMESTAMP THEN 0 ELSE 1 END, submitted_at ASC')
                    ->limit(5)
                    ->get()
                    ->map(fn (AppRequest $r) => [
                        'id' => $r->id,
                        'reference_number' => $r->reference_number,
                        'request_type' => ['value' => $r->legal_process->value, 'label' => $r->legal_process->label(), 'description' => $r->legal_process->description()],
                        'status' => $r->status->value,
                        'agency' => $r->agency->name,
                        'request_due_at' => $r->request_due_at?->toISOString(),
                    ]),
            ];
        }

        if (in_array(Role::InstitutionStaff->value, $roles)) {
            $assignmentBase = RequestAssignment::where('assigned_to', $user->id)
                ->whereHas('request', fn ($q) => $q->whereNotIn('status', [
                    RequestStatus::Released->value,
                    RequestStatus::Closed->value,
                ]));

            $stats['staff'] = [
                'assigned' => (clone $assignmentBase)->count(),
                'in_progress' => (clone $assignmentBase)
                    ->whereHas('request', fn ($q) => $q->where('status', RequestStatus::InProgress))
                    ->count(),
                'recent' => (clone $assignmentBase)
                    ->with(['request.agency'])
                    ->latest('assigned_at')
                    ->limit(5)
                    ->get()
                    ->map(fn ($a) => [
                        'id' => $a->request->id,
                        'reference_number' => $a->request->reference_number,
                        'request_type' => ['value' => $a->request->legal_process->value, 'label' => $a->request->legal_process->label(), 'description' => $a->request->legal_process->description()],
                        'status' => $a->request->status->value,
                        'agency' => $a->request->agency->name,
                        'assigned_at' => $a->assigned_at->toISOString(),
                    ]),
            ];
        }

        return Inertia::render('dashboard', ['stats' => $stats]);
    }
}

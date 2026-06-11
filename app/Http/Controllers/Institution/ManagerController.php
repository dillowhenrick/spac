<?php

namespace App\Http\Controllers\Institution;

use App\Enums\AuditEvent;
use App\Enums\RequestStatus;
use App\Enums\ResponseStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\RejectResponseRequest;
use App\Models\Membership;
use App\Models\Request as AppRequest;
use App\Notifications\ResponseApprovedNotification;
use App\Notifications\ResponseReleasedNotification;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Inertia\Inertia;
use Inertia\Response;

class ManagerController extends Controller
{
    public function queue(HttpRequest $httpRequest): Response
    {
        $this->authorize('manageQueue', AppRequest::class);

        $institutionId = auth()->user()
            ->institutionMemberships()
            ->where('role', Role::InstitutionManager->value)
            ->value('organization_id');

        $activeStatuses = [
            RequestStatus::Routed->value,
            RequestStatus::Assigned->value,
            RequestStatus::InProgress->value,
            RequestStatus::PendingManagerReview->value,
        ];

        $query = AppRequest::where('target_institution_id', $institutionId)
            ->whereIn('status', $activeStatuses)
            ->with(['agency', 'requester', 'assignments.assignedTo'])
            ->latest();

        if ($status = $httpRequest->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $httpRequest->query('date_from')) {
            $query->whereDate('submitted_at', '>=', $from);
        }

        if ($to = $httpRequest->query('date_to')) {
            $query->whereDate('submitted_at', '<=', $to);
        }

        $requests = $query->get();

        return Inertia::render('institution/queue', [
            'requests' => $requests->map(fn (AppRequest $r) => [
                'id' => $r->id,
                'reference_number' => $r->reference_number,
                'request_type' => ['value' => $r->legal_process->value, 'label' => $r->legal_process->label(), 'description' => $r->legal_process->description()],
                'status' => $r->status->value,
                'agency' => $r->agency->name,
                'requester' => $r->requester->name,
                'assigned_to' => $r->assignments->map(fn ($a) => $a->assignedTo->name)->join(', ') ?: null,
                'submitted_at' => $r->submitted_at?->toISOString(),
                'request_due_at' => $r->request_due_at?->toISOString(),
            ]),
            'filters' => $httpRequest->only(['status', 'date_from', 'date_to']),
        ]);
    }

    public function show(AppRequest $request): Response
    {
        $this->authorize('viewAsManager', $request);

        $request->load(['agency', 'requester', 'targetInstitution', 'attachments', 'assignments.assignedTo', 'response.attachments', 'messages.sender']);

        $institutionId = $request->target_institution_id;

        $staff = Membership::where('organization_type', 'institution')
            ->where('organization_id', $institutionId)
            ->whereIn('role', [Role::InstitutionStaff->value, Role::InstitutionManager->value])
            ->with('user')
            ->get()
            ->map(fn ($m) => ['id' => $m->user->id, 'name' => $m->user->name]);

        return Inertia::render('institution/show', [
            'request' => [
                'id' => $request->id,
                'reference_number' => $request->reference_number,
                'additional_context' => $request->additional_context,
                'request_type' => ['value' => $request->legal_process->value, 'label' => $request->legal_process->label(), 'description' => $request->legal_process->description()],
                'nature_of_case' => $request->nature_of_case?->label(),
                'status' => $request->status->value,
                'agency' => $request->agency->name,
                'requester' => $request->requester->name,
                'target_institution' => $request->targetInstitution->name,
                'submitted_at' => $request->submitted_at?->toISOString(),
                'legal_process_signed_at' => $request->legal_process_signed_at?->toISOString(),
                'warrant_expires_at' => $request->warrant_expires_at?->toISOString(),
                'request_due_at' => $request->request_due_at?->toISOString(),
                'attachments' => $request->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'original_name' => $a->original_name,
                    'size' => $a->size,
                ]),
                'assignments' => $request->assignments->map(fn ($a) => [
                    'id' => $a->id,
                    'assigned_to' => ['id' => $a->assignedTo->id, 'name' => $a->assignedTo->name],
                    'is_self_assigned' => $a->is_self_assigned,
                    'assigned_at' => $a->assigned_at->toISOString(),
                ]),
                'response' => $request->response ? [
                    'id' => $request->response->id,
                    'status' => $request->response->status->value,
                    'notes' => $request->response->notes,
                    'submitted_at' => $request->response->submitted_at?->toISOString(),
                    'attachments' => $request->response->attachments->map(fn ($a) => [
                        'id' => $a->id,
                        'original_name' => $a->original_name,
                        'size' => $a->size,
                    ]),
                ] : null,
                'messages' => $request->messages->map(fn ($m) => [
                    'id' => $m->id,
                    'body' => $m->body,
                    'sender' => $m->sender->name,
                    'is_mine' => $m->sender_id === auth()->id(),
                    'created_at' => $m->created_at->toISOString(),
                ]),
                'can' => [
                    'assign' => auth()->user()->can('assign', $request),
                    'approve_response' => auth()->user()->can('approveResponse', $request),
                    'reject_response' => auth()->user()->can('rejectResponse', $request),
                    'release_response' => auth()->user()->can('releaseResponse', $request),
                ],
            ],
            'staff' => $staff,
        ]);
    }

    public function assignSelf(AppRequest $request): RedirectResponse
    {
        $this->authorize('assign', $request);

        $request->assignments()->delete();
        $request->assignments()->create([
            'assigned_by' => auth()->id(),
            'assigned_to' => auth()->id(),
            'is_self_assigned' => true,
            'assigned_at' => now(),
        ]);

        $request->update(['status' => RequestStatus::Assigned]);

        AuditService::log(AuditEvent::AssignedToSelf, $request, [], ['assigned_to' => auth()->id()]);

        return redirect()->route('institution.show', $request);
    }

    public function assign(AssignStaffRequest $httpRequest, AppRequest $request): RedirectResponse
    {
        $this->authorize('assign', $request);

        $request->assignments()->delete();

        foreach ($httpRequest->validated()['staff_ids'] as $staffId) {
            $request->assignments()->create([
                'assigned_by' => auth()->id(),
                'assigned_to' => $staffId,
                'is_self_assigned' => false,
                'assigned_at' => now(),
            ]);
        }

        $request->update(['status' => RequestStatus::Assigned]);

        AuditService::log(AuditEvent::AssignedToStaff, $request, [], ['staff_ids' => $httpRequest->validated()['staff_ids']]);

        return redirect()->route('institution.show', $request);
    }

    public function approveResponse(AppRequest $request): RedirectResponse
    {
        $this->authorize('approveResponse', $request);

        $request->response()->update(['status' => ResponseStatus::Approved]);
        $request->update(['status' => RequestStatus::Approved]);

        $request->assignments->each(fn ($a) => $a->assignedTo->notify(new ResponseApprovedNotification($request)));

        AuditService::log(AuditEvent::ResponseApproved, $request, ['status' => 'pending_approval'], ['status' => 'approved']);

        return redirect()->route('institution.show', $request);
    }

    public function rejectResponse(RejectResponseRequest $httpRequest, AppRequest $request): RedirectResponse
    {
        $this->authorize('rejectResponse', $request);

        $request->response()->update([
            'status' => ResponseStatus::Rejected,
            'notes' => $httpRequest->validated()['notes'],
        ]);

        $request->update(['status' => RequestStatus::RevisionRequested]);

        AuditService::log(AuditEvent::ResponseRejected, $request, ['status' => 'pending_approval'], ['status' => 'rejected']);

        return redirect()->route('institution.show', $request);
    }

    public function releaseResponse(AppRequest $request): RedirectResponse
    {
        $this->authorize('releaseResponse', $request);

        $request->response()->update(['status' => ResponseStatus::Released]);
        $request->update(['status' => RequestStatus::Released]);

        $request->requester->notify(new ResponseReleasedNotification($request));

        AuditService::log(AuditEvent::ResponseReleased, $request, ['status' => 'approved'], ['status' => 'released']);

        return redirect()->route('institution.show', $request);
    }
}

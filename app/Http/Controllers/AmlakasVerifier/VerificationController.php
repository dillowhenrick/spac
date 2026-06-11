<?php

namespace App\Http\Controllers\AmlakasVerifier;

use App\Enums\AuditEvent;
use App\Enums\RequestStatus;
use App\Enums\VerificationDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveVerificationRequest;
use App\Http\Requests\RejectVerificationRequest;
use App\Models\Request as AppRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function index(HttpRequest $httpRequest): Response
    {
        $this->authorize('inspectAny', AppRequest::class);

        $query = AppRequest::whereIn('status', [
            RequestStatus::Submitted->value,
            RequestStatus::UnderVerification->value,
        ])
            ->with(['agency', 'requester', 'targetInstitution'])
            ->latest('submitted_at');

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

        return Inertia::render('verification/index', [
            'requests' => $requests->map(fn (AppRequest $r) => [
                'id' => $r->id,
                'reference_number' => $r->reference_number,
                'request_type' => ['value' => $r->legal_process->value, 'label' => $r->legal_process->label(), 'description' => $r->legal_process->description()],
                'status' => $r->status->value,
                'agency' => $r->agency->name,
                'requester' => $r->requester->name,
                'target_institution' => $r->targetInstitution->name,
                'submitted_at' => $r->submitted_at?->toISOString(),
            ]),
            'filters' => $httpRequest->only(['status', 'date_from', 'date_to']),
        ]);
    }

    public function show(AppRequest $request): Response
    {
        $this->authorize('inspect', $request);

        $request->load(['agency', 'requester', 'targetInstitution', 'attachments', 'verification', 'requestRoute']);

        return Inertia::render('verification/show', [
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
                    'mime_type' => $a->mime_type,
                    'size' => $a->size,
                ]),
                'verification' => $request->verification ? [
                    'decision' => $request->verification->decision->value,
                    'notes' => $request->verification->notes,
                    'verified_at' => $request->verification->verified_at->toISOString(),
                ] : null,
                'routed' => $request->requestRoute !== null,
                'can' => [
                    'approve' => auth()->user()->can('approve', $request),
                    'reject' => auth()->user()->can('reject', $request),
                    'route' => auth()->user()->can('routeRequest', $request),
                ],
            ],
        ]);
    }

    public function approve(ApproveVerificationRequest $httpRequest, AppRequest $request): RedirectResponse
    {
        $this->authorize('approve', $request);

        $request->verification()->create([
            'verified_by' => auth()->id(),
            'decision' => VerificationDecision::Approved,
            'notes' => $httpRequest->validated()['notes'] ?? null,
            'verified_at' => now(),
        ]);

        $request->update(['status' => RequestStatus::Verified]);

        AuditService::log(AuditEvent::RequestVerified, $request, ['status' => 'submitted'], ['status' => 'verified']);

        return redirect()->route('verification.show', $request);
    }

    public function reject(RejectVerificationRequest $httpRequest, AppRequest $request): RedirectResponse
    {
        $this->authorize('reject', $request);

        $request->verification()->create([
            'verified_by' => auth()->id(),
            'decision' => VerificationDecision::Rejected,
            'notes' => $httpRequest->validated()['notes'],
            'verified_at' => now(),
        ]);

        $request->update(['status' => RequestStatus::VerificationRejected]);

        AuditService::log(AuditEvent::RequestRejected, $request, ['status' => 'submitted'], ['status' => 'verification_rejected']);

        return redirect()->route('verification.show', $request);
    }

    public function route(AppRequest $request): RedirectResponse
    {
        $this->authorize('routeRequest', $request);

        $request->requestRoute()->create([
            'routed_by' => auth()->id(),
            'institution_id' => $request->target_institution_id,
            'routed_at' => now(),
        ]);

        $request->update(['status' => RequestStatus::Routed]);

        AuditService::log(AuditEvent::RequestRouted, $request, ['status' => 'verified'], ['status' => 'routed']);

        return redirect()->route('verification.show', $request);
    }
}

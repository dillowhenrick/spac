<?php

namespace App\Http\Controllers\Institution;

use App\Enums\AuditEvent;
use App\Enums\RequestStatus;
use App\Enums\ResponseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResponseRequest;
use App\Models\Request as AppRequest;
use App\Models\RequestAssignment;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(HttpRequest $httpRequest): Response
    {
        $query = RequestAssignment::where('assigned_to', auth()->id())
            ->with(['request.agency', 'request.targetInstitution', 'request.response']);

        if ($status = $httpRequest->query('status')) {
            $query->whereHas('request', fn ($q) => $q->where('status', $status));
        }

        if ($from = $httpRequest->query('date_from')) {
            $query->whereDate('assigned_at', '>=', $from);
        }

        if ($to = $httpRequest->query('date_to')) {
            $query->whereDate('assigned_at', '<=', $to);
        }

        $assignments = $query->get();

        return Inertia::render('institution/assigned', [
            'assignments' => $assignments->map(fn ($a) => [
                'id' => $a->id,
                'request' => [
                    'id' => $a->request->id,
                    'reference_number' => $a->request->reference_number,
                    'request_type' => ['value' => $a->request->legal_process->value, 'label' => $a->request->legal_process->label(), 'description' => $a->request->legal_process->description()],
                    'status' => $a->request->status->value,
                    'agency' => $a->request->agency->name,
                    'target_institution' => $a->request->targetInstitution->name,
                    'response_status' => $a->request->response?->status->value,
                ],
                'assigned_at' => $a->assigned_at->toISOString(),
            ]),
            'filters' => $httpRequest->only(['status', 'date_from', 'date_to']),
        ]);
    }

    public function show(AppRequest $request): Response
    {
        $this->authorize('viewAsStaff', $request);

        $request->load(['agency', 'requester', 'targetInstitution', 'attachments', 'response.attachments', 'messages.sender']);

        return Inertia::render('institution/work-area', [
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
                    'draft_response' => auth()->user()->can('draftResponse', $request),
                    'submit_response' => auth()->user()->can('submitResponse', $request),
                    'send_message' => auth()->user()->can('sendMessage', $request),
                ],
            ],
        ]);
    }

    public function storeResponse(StoreResponseRequest $httpRequest, AppRequest $request): RedirectResponse
    {
        $this->authorize('draftResponse', $request);

        $response = $request->response()->updateOrCreate(
            ['request_id' => $request->id],
            [
                'drafted_by' => auth()->id(),
                'notes' => $httpRequest->validated()['notes'] ?? null,
                'status' => ResponseStatus::Draft,
            ]
        );

        if ($httpRequest->hasFile('attachments')) {
            foreach ($httpRequest->file('attachments') as $file) {
                $path = $file->store('response-attachments', 'local');

                $response->attachments()->create([
                    'uploaded_by' => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        if ($request->status === RequestStatus::Assigned) {
            $executedAt = now();
            $request->update([
                'status' => RequestStatus::InProgress,
                'executed_at' => $executedAt,
                'request_due_at' => $request->computeReturnDueAt($executedAt),
            ]);
        }

        AuditService::log(AuditEvent::ResponseDrafted, $request);

        return redirect()->route('institution.assigned.show', $request);
    }

    public function submitResponse(AppRequest $request): RedirectResponse
    {
        $this->authorize('submitResponse', $request);

        $request->response()->update([
            'status' => ResponseStatus::PendingApproval,
            'submitted_at' => now(),
        ]);

        $request->update(['status' => RequestStatus::PendingManagerReview]);

        AuditService::log(AuditEvent::ResponseSubmitted, $request, ['status' => 'draft'], ['status' => 'pending_approval']);

        return redirect()->route('institution.assigned.show', $request);
    }
}

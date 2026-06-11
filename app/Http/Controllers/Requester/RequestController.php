<?php

namespace App\Http\Controllers\Requester;

use App\Enums\AuditEvent;
use App\Enums\LegalProcess;
use App\Enums\NatureOfCase;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestRequest;
use App\Models\Institution;
use App\Models\Request as AppRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function index(HttpRequest $httpRequest): Response
    {
        $this->authorize('viewAny', AppRequest::class);

        $query = AppRequest::where('requester_id', auth()->id())
            ->with('targetInstitution')
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

        return Inertia::render('requests/index', [
            'requests' => $requests->map(fn (AppRequest $r) => [
                'id' => $r->id,
                'reference_number' => $r->reference_number,
                'request_type' => ['value' => $r->legal_process->value, 'label' => $r->legal_process->label(), 'description' => $r->legal_process->description()],
                'status' => $r->status->value,
                'target_institution' => $r->targetInstitution->name,
                'submitted_at' => $r->submitted_at?->toISOString(),
                'created_at' => $r->created_at->toISOString(),
            ]),
            'filters' => $httpRequest->only(['status', 'date_from', 'date_to']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', AppRequest::class);

        return Inertia::render('requests/create', [
            'institutions' => Institution::where('is_active', true)->get(['id', 'name', 'code']),
            'requestTypes' => collect(LegalProcess::cases())->map(fn (LegalProcess $lp) => [
                'value' => $lp->value,
                'label' => $lp->label(),
                'description' => $lp->description(),
            ]),
            'natureOfCaseOptions' => collect(NatureOfCase::cases())->map(fn (NatureOfCase $n) => [
                'value' => $n->value,
                'label' => $n->label(),
            ]),
        ]);
    }

    public function store(StoreRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', AppRequest::class);

        $membership = auth()->user()->agencyMemberships()->firstOrFail();

        $validated = $request->validated();

        $appRequest = AppRequest::create([
            ...$validated,
            'legal_process' => $validated['request_type'],
            'requester_id' => auth()->id(),
            'agency_id' => $membership->organization_id,
            'status' => RequestStatus::Draft,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('request-attachments', 'local');

                $appRequest->attachments()->create([
                    'uploaded_by' => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        AuditService::log(AuditEvent::RequestCreated, $appRequest, [], ['status' => 'draft']);

        return redirect()->route('requests.show', $appRequest);
    }

    public function show(AppRequest $request): Response
    {
        $this->authorize('view', $request);

        $request->load(['agency', 'targetInstitution', 'attachments', 'response.attachments', 'messages.sender']);

        return Inertia::render('requests/show', [
            'request' => [
                'id' => $request->id,
                'reference_number' => $request->reference_number,
                'additional_context' => $request->additional_context,
                'request_type' => ['value' => $request->legal_process->value, 'label' => $request->legal_process->label(), 'description' => $request->legal_process->description()],
                'nature_of_case' => $request->nature_of_case?->label(),
                'status' => $request->status->value,
                'agency' => $request->agency->name,
                'target_institution' => $request->targetInstitution->name,
                'submitted_at' => $request->submitted_at?->toISOString(),
                'legal_process_signed_at' => $request->legal_process_signed_at?->toISOString(),
                'warrant_expires_at' => $request->warrant_expires_at?->toISOString(),
                'request_due_at' => $request->request_due_at?->toISOString(),
                'created_at' => $request->created_at->toISOString(),
                'attachments' => $request->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'original_name' => $a->original_name,
                    'mime_type' => $a->mime_type,
                    'size' => $a->size,
                    'created_at' => $a->created_at->toISOString(),
                ]),
                'response' => $request->response && $request->response->status->value === 'released' ? [
                    'notes' => $request->response->notes,
                    'released_at' => $request->response->updated_at->toISOString(),
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
                    'submit' => auth()->user()->can('submit', $request),
                    'send_message' => auth()->user()->can('sendMessage', $request),
                ],
            ],
        ]);
    }

    public function submit(AppRequest $request): RedirectResponse
    {
        $this->authorize('submit', $request);

        $request->update([
            'status' => RequestStatus::Submitted,
            'submitted_at' => now(),
        ]);

        AuditService::log(AuditEvent::RequestSubmitted, $request, ['status' => 'draft'], ['status' => 'submitted']);

        return redirect()->route('requests.show', $request);
    }
}

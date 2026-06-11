<?php

namespace App\Http\Controllers;

use App\Enums\AuditEvent;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Request as AppRequest;
use App\Models\RequestAssignment;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $httpRequest, AppRequest $request): RedirectResponse
    {
        $this->authorize('sendMessage', $request);

        $message = $request->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $httpRequest->validated()['body'],
        ]);

        if ($httpRequest->hasFile('attachments')) {
            foreach ($httpRequest->file('attachments') as $file) {
                $path = $file->store('message-attachments', 'local');

                $message->attachments()->create([
                    'uploaded_by' => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        $message->load('sender');

        $this->notifyParticipants($request, $message);

        AuditService::log(AuditEvent::MessageSent, $request);

        return back();
    }

    private function notifyParticipants(AppRequest $request, $message): void
    {
        $senderId = auth()->id();

        $participants = collect();

        // Requester
        $participants->push($request->requester);

        // Assigned staff
        RequestAssignment::where('request_id', $request->id)
            ->with('assignedTo')
            ->get()
            ->each(fn ($a) => $participants->push($a->assignedTo));

        // Manager of institution (via membership)
        User::whereHas('institutionMemberships', fn ($q) => $q
            ->where('organization_id', $request->target_institution_id)
            ->where('role', 'institution_manager')
        )->get()->each(fn ($u) => $participants->push($u));

        $participants
            ->unique('id')
            ->filter(fn (User $u) => $u->id !== $senderId)
            ->each(fn (User $u) => $u->notify(new NewMessageNotification($request, $message)));
    }
}

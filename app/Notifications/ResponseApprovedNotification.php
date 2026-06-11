<?php

namespace App\Notifications;

use App\Models\Request as AppRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ResponseApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AppRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'response_approved',
            'request_id' => $this->request->id,
            'reference_number' => $this->request->reference_number,
            'message' => "Response for {$this->request->reference_number} has been approved by the manager.",
        ];
    }
}

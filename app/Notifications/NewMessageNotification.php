<?php

namespace App\Notifications;

use App\Models\Request as AppRequest;
use App\Models\RequestMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AppRequest $request,
        public RequestMessage $message
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_message',
            'request_id' => $this->request->id,
            'reference_number' => $this->request->reference_number,
            'sender' => $this->message->sender->name,
            'message' => "New message on request {$this->request->reference_number} from {$this->message->sender->name}.",
        ];
    }
}

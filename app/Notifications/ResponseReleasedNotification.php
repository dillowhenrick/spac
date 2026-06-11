<?php

namespace App\Notifications;

use App\Models\Request as AppRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResponseReleasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AppRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Response ready: {$this->request->reference_number}")
            ->line("Your request {$this->request->reference_number} has a response ready for download.")
            ->action('View Request', url("/requests/{$this->request->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'response_released',
            'request_id' => $this->request->id,
            'reference_number' => $this->request->reference_number,
            'message' => "Your request {$this->request->reference_number} has been completed.",
        ];
    }
}

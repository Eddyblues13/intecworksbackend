<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;
    public $message;
    public $jobId;
    public $eventType;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, string $jobId, string $eventType)
    {
        $this->title = $title;
        $this->message = $message;
        $this->jobId = $jobId;
        $this->eventType = $eventType; // e.g. 'job_accepted', 'quote_submitted', etc.
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\FcmChannel::class];
    }

    /**
     * Get the payload for the Firebase Cloud Messaging service.
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->message,
            'data'  => [
                'job_id'     => $this->jobId,
                'event_type' => $this->eventType,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'      => $this->title,
            'message'    => $this->message,
            'job_id'     => $this->jobId,
            'event_type' => $this->eventType,
        ];
    }
}

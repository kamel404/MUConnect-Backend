<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Application;

class ApplicationStatusChanged extends Notification
{
    use Queueable;

    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $statusMessages = [
            'accepted' => 'Your application has been accepted',
            'declined' => 'Your application has been declined',
            'cancelled' => 'Your application has been cancelled'
        ];

        return [
            'type' => 'application_status_changed',
            'message' => $statusMessages[$this->application->status] ?? 'Your application status has been updated',
            'application_id' => $this->application->id,
            'request_id' => $this->application->request_id,
            'status' => $this->application->status,
            'course_name' => $this->application->request->course_name
        ];
    }
}
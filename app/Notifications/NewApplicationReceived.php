<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Application;

class NewApplicationReceived extends Notification
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
        return [
            'type' => 'new_application',
            'message' => 'New application received for your section change request',
            'application_id' => $this->application->id,
            'request_id' => $this->application->request_id,
            'user_id' => $this->application->user_id,
            'user_name' => $this->application->user->first_name . ' ' . $this->application->user->last_name,
            'course_name' => $this->application->request->course_name
        ];
    }
}
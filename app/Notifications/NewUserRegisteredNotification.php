<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification
{
    use Queueable;

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'New User Registered',
            'message' => $this->user->first_name . ' ' . $this->user->last_name . ' has registered.',
            'user_id' => $this->user->id,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
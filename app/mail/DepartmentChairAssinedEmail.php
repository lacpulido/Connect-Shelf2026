<?php

namespace App\Mail;

use App\Models\Department;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepartmentChairAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Department $department;

    public function __construct(User $user, Department $department)
    {
        $this->user = $user;
        $this->department = $department;
    }

    public function build()
    {
        return $this->subject('Department Chairperson Assignment Notification')
            ->markdown('emails.department-chair-assigned')
            ->with([
                'user' => $this->user,
                'department' => $this->department,
            ]);
    }
}
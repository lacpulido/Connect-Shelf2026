<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PanelistAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public User $panelist,
        public string $scheduleText
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Panelist Assignment Notification'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.panelist-assigned'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
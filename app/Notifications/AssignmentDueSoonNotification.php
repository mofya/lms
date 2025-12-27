<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentDueSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Assignment $assignment
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Assignment Due Soon: {$this->assignment->title}")
            ->line("Your assignment '{$this->assignment->title}' is due soon.")
            ->line("Due Date: {$this->assignment->due_at->format('F j, Y g:i A')}")
            ->action('View Assignment', url("/student/assignments/{$this->assignment->id}"))
            ->line('Thank you for using our LMS!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'assignment_due_soon',
            'assignment_id' => $this->assignment->id,
            'assignment_title' => $this->assignment->title,
            'due_at' => $this->assignment->due_at->toIso8601String(),
            'message' => "Assignment '{$this->assignment->title}' is due soon.",
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\AssignmentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GradePostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AssignmentSubmission $submission
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $grade = $this->submission->grade;
        $score = $grade->final_score ?? $grade->ai_score ?? 'Pending';
        $maxPoints = $this->submission->assignment->max_points;

        return (new MailMessage)
            ->subject("Grade Posted: {$this->submission->assignment->title}")
            ->line("Your grade has been posted for '{$this->submission->assignment->title}'.")
            ->line("Score: {$score}/{$maxPoints}")
            ->action('View Grade', url("/student/assignments/{$this->submission->assignment->id}"))
            ->line('Thank you for using our LMS!');
    }

    public function toArray($notifiable): array
    {
        $grade = $this->submission->grade;
        $score = $grade->final_score ?? $grade->ai_score ?? 'Pending';
        $maxPoints = $this->submission->assignment->max_points;

        return [
            'type' => 'grade_posted',
            'submission_id' => $this->submission->id,
            'assignment_title' => $this->submission->assignment->title,
            'score' => $score,
            'max_points' => $maxPoints,
            'message' => "Grade posted for '{$this->submission->assignment->title}': {$score}/{$maxPoints}",
        ];
    }
}

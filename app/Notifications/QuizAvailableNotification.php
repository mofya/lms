<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Quiz $quiz
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Quiz Available: {$this->quiz->title}")
            ->line("A new quiz '{$this->quiz->title}' is now available.")
            ->line("Course: {$this->quiz->course->title}")
            ->action('Take Quiz', url("/student/quizzes/{$this->quiz->id}"))
            ->line('Thank you for using our LMS!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'quiz_available',
            'quiz_id' => $this->quiz->id,
            'quiz_title' => $this->quiz->title,
            'course_name' => $this->quiz->course->title,
            'message' => "New quiz available: {$this->quiz->title}",
        ];
    }
}

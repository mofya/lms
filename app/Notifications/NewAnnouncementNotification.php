<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Announcement $announcement
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $courseName = $this->announcement->course ? $this->announcement->course->title : 'System-wide';
        
        return (new MailMessage)
            ->subject("New Announcement: {$this->announcement->title}")
            ->line("A new announcement has been posted for {$courseName}.")
            ->line("Title: {$this->announcement->title}")
            ->line(strip_tags(substr($this->announcement->content, 0, 200)) . '...')
            ->action('View Announcement', url('/student'))
            ->line('Thank you for using our LMS!');
    }

    public function toArray($notifiable): array
    {
        $courseName = $this->announcement->course ? $this->announcement->course->title : 'System-wide';

        return [
            'type' => 'new_announcement',
            'announcement_id' => $this->announcement->id,
            'announcement_title' => $this->announcement->title,
            'course_name' => $courseName,
            'message' => "New announcement: {$this->announcement->title}",
        ];
    }
}

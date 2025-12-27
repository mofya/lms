<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Notifications\NewAnnouncementNotification;
use Illuminate\Support\Facades\Notification;

class AnnouncementObserver
{
    /**
     * Handle the Announcement "created" event.
     */
    public function created(Announcement $announcement): void
    {
        if (!$announcement->isPublished()) {
            return;
        }

        // Notify enrolled students
        if ($announcement->course_id) {
            $students = $announcement->course->students;
        } else {
            // System-wide: notify all students
            $students = \App\Models\User::where('is_admin', false)->get();
        }

        Notification::send($students, new NewAnnouncementNotification($announcement));
    }
}

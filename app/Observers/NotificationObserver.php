<?php

namespace App\Observers;

use App\Models\Assignment;
use App\Notifications\AssignmentDueSoonNotification;
use Illuminate\Support\Facades\Notification;

class NotificationObserver
{
    /**
     * Schedule assignment due soon notifications (called by scheduler)
     */
    public static function sendAssignmentDueSoonNotifications(): void
    {
        $assignments = Assignment::where('is_published', true)
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->where('due_at', '<=', now()->addDays(2))
            ->get();

        foreach ($assignments as $assignment) {
            $students = $assignment->course->students;
            Notification::send($students, new AssignmentDueSoonNotification($assignment));
        }
    }
}

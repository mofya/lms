<?php

namespace App\Observers;

use App\Models\AssignmentSubmission;
use App\Notifications\GradePostedNotification;

class AssignmentSubmissionNotificationObserver
{
    /**
     * Handle the AssignmentSubmission "updated" event when graded.
     */
    public function updated(AssignmentSubmission $submission): void
    {
        // Notify student when grade is posted
        if ($submission->isDirty('status') && $submission->isGraded() && $submission->grade) {
            $submission->user->notify(new GradePostedNotification($submission));
        }
        
        // Award XP and check badges
        if ($submission->isDirty('status') && $submission->status !== 'draft') {
            (new \App\Services\XpService())->awardAssignmentSubmission($submission->user);
            (new \App\Services\XpService())->updateStreak($submission->user);
            \App\Observers\BadgeObserver::checkBadges($submission->user);
        }
    }
}

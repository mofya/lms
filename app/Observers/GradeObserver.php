<?php

namespace App\Observers;

use App\Models\Grade;
use App\Models\Test;
use App\Models\AssignmentSubmission;

class GradeObserver
{
    /**
     * Handle the Test "created" event.
     */
    public function created(Test $test): void
    {
        if ($test->quiz && $test->quiz->course) {
            $this->updateGradeForUserCourse($test->user, $test->quiz->course);
            
            // Award XP for quiz completion
            $totalQuestions = $test->quiz->questions()->count();
            (new \App\Services\XpService())->awardQuizCompletion($test->user, $test->score, $totalQuestions);
            (new \App\Services\XpService())->updateStreak($test->user);
        }
    }

    /**
     * Handle the AssignmentSubmission "updated" event.
     */
    public function updated(AssignmentSubmission $submission): void
    {
        // Only recalculate if status changed to graded/approved
        if ($submission->isDirty('status') && $submission->isGraded() && $submission->assignment && $submission->assignment->course) {
            $this->updateGradeForUserCourse($submission->user, $submission->assignment->course);
        }
    }

    protected function updateGradeForUserCourse($user, $course): void
    {
        if (!$user || !$course) {
            return;
        }

        $grade = Grade::getOrCreateForUserCourse($user, $course);
        $grade->recalculate();
        
        // Check for badge eligibility
        \App\Observers\BadgeObserver::checkBadges($user);
    }
}

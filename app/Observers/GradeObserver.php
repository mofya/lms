<?php

namespace App\Observers;

use App\Models\Grade;
use App\Models\Test;
use App\Models\AssignmentSubmission;

class GradeObserver
{
    /**
     * Handle the "created" event for both Test and AssignmentSubmission.
     */
    public function created(Test|AssignmentSubmission $model): void
    {
        if ($model instanceof Test) {
            $this->handleTestCreated($model);
        }
        // AssignmentSubmission creation doesn't trigger grade recalculation
    }

    /**
     * Handle Test creation specifically.
     */
    protected function handleTestCreated(Test $test): void
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
    public function updated(Test|AssignmentSubmission $model): void
    {
        if ($model instanceof AssignmentSubmission) {
            // Only recalculate if status changed to graded/approved
            if ($model->isDirty('status') && $model->isGraded() && $model->assignment && $model->assignment->course) {
                $this->updateGradeForUserCourse($model->user, $model->assignment->course);
            }
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

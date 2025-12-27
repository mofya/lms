<?php

namespace App\Observers;

use App\Models\Quiz;
use App\Notifications\QuizAvailableNotification;
use Illuminate\Support\Facades\Notification;

class QuizObserver
{
    /**
     * Handle the Quiz "created" event when published.
     */
    public function created(Quiz $quiz): void
    {
        if (!$quiz->is_published || !$quiz->course) {
            return;
        }

        $students = $quiz->course->students;
        Notification::send($students, new QuizAvailableNotification($quiz));
    }
}

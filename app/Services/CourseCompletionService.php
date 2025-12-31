<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Collection;

class CourseCompletionService
{
    public function __construct(
        protected CertificateGenerator $certificateGenerator
    ) {}

    public function hasCompletedAllQuizzes(User $user, Course $course): bool
    {
        $requiredQuizzes = $this->getRequiredQuizzes($course);

        if ($requiredQuizzes->isEmpty()) {
            return false;
        }

        foreach ($requiredQuizzes as $quiz) {
            if (! $this->hasPassedQuiz($user, $quiz)) {
                return false;
            }
        }

        return true;
    }

    public function getRequiredQuizzes(Course $course): Collection
    {
        $quizzes = $course->quizzes()->published()->get();
        $requiredQuizzes = $quizzes->filter(fn (Quiz $quiz) => $quiz->require_passing_to_proceed);

        if ($requiredQuizzes->isEmpty()) {
            return $quizzes;
        }

        return $requiredQuizzes;
    }

    public function hasPassedQuiz(User $user, Quiz $quiz): bool
    {
        $bestAttempt = Test::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('result')
            ->first();

        if (! $bestAttempt) {
            return false;
        }

        if ($quiz->passing_score) {
            $totalQuestions = $quiz->questions()->count();
            if ($totalQuestions === 0) {
                return true;
            }

            return $quiz->isPassing($bestAttempt->result ?? 0, $totalQuestions);
        }

        return true;
    }

    public function checkAndAwardCertificate(User $user, Course $course): ?Certificate
    {
        $existingCertificate = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingCertificate) {
            return $existingCertificate;
        }

        if (! $this->hasCompletedAllQuizzes($user, $course)) {
            return null;
        }

        return $this->certificateGenerator->generateCertificate($user, $course);
    }

    public function getCourseQuizProgress(User $user, Course $course): array
    {
        $quizzes = $course->quizzes()->published()->get();

        if ($quizzes->isEmpty()) {
            return [
                'total_quizzes' => 0,
                'passed_quizzes' => 0,
                'percentage' => 0,
            ];
        }

        $passedCount = 0;
        foreach ($quizzes as $quiz) {
            if ($this->hasPassedQuiz($user, $quiz)) {
                $passedCount++;
            }
        }

        return [
            'total_quizzes' => $quizzes->count(),
            'passed_quizzes' => $passedCount,
            'percentage' => (int) round(($passedCount / $quizzes->count()) * 100),
        ];
    }
}

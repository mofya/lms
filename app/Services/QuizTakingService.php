<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuizTakingService
{
    /**
     * Check if a user can attempt a quiz.
     */
    public function canUserAttemptQuiz(User $user, Quiz $quiz): bool
    {
        // If unlimited attempts allowed
        if ($quiz->attempts_allowed === null || $quiz->attempts_allowed === 0) {
            return true;
        }

        // Count completed attempts
        $completedAttempts = Test::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->count();

        return $completedAttempts < $quiz->attempts_allowed;
    }

    /**
     * Get the number of remaining attempts for a user on a quiz.
     */
    public function getRemainingAttempts(User $user, Quiz $quiz): ?int
    {
        if ($quiz->attempts_allowed === null || $quiz->attempts_allowed === 0) {
            return null; // Unlimited
        }

        $completedAttempts = Test::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->count();

        return max(0, $quiz->attempts_allowed - $completedAttempts);
    }

    /**
     * Start a new quiz attempt.
     */
    public function startQuiz(User $user, Quiz $quiz): Test
    {
        if (! $this->canUserAttemptQuiz($user, $quiz)) {
            throw new Exception('User is not allowed to attempt this quiz. Maximum attempts reached.');
        }

        // Get the next attempt number
        $attemptNumber = Test::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->max('attempt_number') ?? 0;

        return Test::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'attempt_number' => $attemptNumber + 1,
            'started_at' => now(),
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Get an in-progress attempt for a user on a quiz.
     */
    public function getInProgressAttempt(User $user, Quiz $quiz): ?Test
    {
        return Test::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();
    }

    /**
     * Get or create an attempt for taking a quiz.
     */
    public function getOrCreateAttempt(User $user, Quiz $quiz): Test
    {
        $existingAttempt = $this->getInProgressAttempt($user, $quiz);

        if ($existingAttempt) {
            return $existingAttempt;
        }

        return $this->startQuiz($user, $quiz);
    }

    /**
     * Prepare quiz questions, optionally shuffled.
     */
    public function prepareQuizQuestions(Quiz $quiz): Collection
    {
        $questions = $quiz->questions()
            ->with('questionOptions')
            ->get();

        if ($quiz->shuffle_questions) {
            return $questions->shuffle()->values();
        }

        return $questions;
    }

    /**
     * Shuffle question options if configured.
     */
    public function shuffleQuestionOptions(Quiz $quiz, Collection $options): Collection
    {
        // Remove the 'correct' field from options to prevent cheating
        $sanitizedOptions = $options->map(function ($option) {
            return (object) [
                'id' => $option->id,
                'option' => $option->option,
                'question_id' => $option->question_id,
            ];
        });

        if ($quiz->shuffle_options) {
            return $sanitizedOptions->shuffle()->values();
        }

        return $sanitizedOptions->values();
    }

    /**
     * Save an answer for a question.
     */
    public function saveAnswer(Test $test, int $questionId, ?int $optionId = null, ?string $userAnswer = null): TestAnswer
    {
        $quiz = $test->quiz()->with('questions.questionOptions')->first();

        // Verify question belongs to this quiz
        if (! $quiz->questions->contains('id', $questionId)) {
            throw new Exception('Question does not belong to this quiz.');
        }

        $question = $quiz->questions->find($questionId);

        // Verify option belongs to question (if option provided)
        if ($optionId !== null && ! $question->questionOptions->contains('id', $optionId)) {
            throw new Exception('Option does not belong to this question.');
        }

        // Create or update the answer
        return TestAnswer::updateOrCreate(
            [
                'test_id' => $test->id,
                'question_id' => $questionId,
            ],
            [
                'user_id' => $test->user_id,
                'option_id' => $optionId,
                'user_answer' => $userAnswer,
                'correct' => null, // Will be evaluated on submission
            ]
        );
    }

    /**
     * Save multiple answers (for checkbox questions).
     */
    public function saveMultipleAnswers(Test $test, int $questionId, array $optionIds): TestAnswer
    {
        return $this->saveAnswer($test, $questionId, null, json_encode($optionIds));
    }

    /**
     * Submit a quiz attempt and calculate the score.
     */
    public function submitQuiz(Test $test): Test
    {
        if ($test->isSubmitted()) {
            throw new Exception('This quiz has already been submitted.');
        }

        return DB::transaction(function () use ($test) {
            $submittedAt = now();
            $timeTaken = $test->started_at
                ? $test->started_at->diffInSeconds($submittedAt)
                : 0;

            // Check time limit if applicable
            $quiz = $test->quiz;
            if ($quiz->total_duration && $timeTaken > ($quiz->total_duration * 60)) {
                // Allow submission but mark as over time (don't throw exception)
            }

            // Calculate score
            $scoreData = $this->calculateScore($test);

            $test->update([
                'submitted_at' => $submittedAt,
                'time_spent' => $timeTaken,
                'result' => $scoreData['correct_count'],
                'correct_count' => $scoreData['correct_count'],
                'wrong_count' => $scoreData['wrong_count'],
            ]);

            return $test->fresh();
        });
    }

    /**
     * Calculate score for a test attempt.
     */
    public function calculateScore(Test $test): array
    {
        $answers = $test->testAnswers()
            ->with(['question.questionOptions'])
            ->get();

        $correctCount = 0;
        $wrongCount = 0;

        foreach ($answers as $answer) {
            $question = $answer->question;

            if (! $question) {
                $wrongCount++;

                continue;
            }

            $isCorrect = $question->evaluateAnswer($answer);

            // Update the answer's correct status
            $answer->update(['correct' => $isCorrect]);

            if ($isCorrect) {
                $correctCount++;
            } else {
                $wrongCount++;
            }
        }

        $totalQuestions = $answers->count();
        $scorePercentage = $totalQuestions > 0
            ? round(($correctCount / $totalQuestions) * 100, 2)
            : 0;

        return [
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'total_questions' => $totalQuestions,
            'score_percentage' => $scorePercentage,
        ];
    }

    /**
     * Check if a test has exceeded its time limit.
     */
    public function hasExceededTimeLimit(Test $test): bool
    {
        $quiz = $test->quiz;

        if (! $quiz->total_duration || ! $test->started_at) {
            return false;
        }

        $elapsedSeconds = $test->started_at->diffInSeconds(now());
        $allowedSeconds = $quiz->total_duration * 60;

        return $elapsedSeconds > $allowedSeconds;
    }

    /**
     * Get remaining time in seconds for a test.
     */
    public function getRemainingTimeSeconds(Test $test): ?int
    {
        $quiz = $test->quiz;

        if (! $quiz->total_duration || ! $test->started_at) {
            return null;
        }

        $elapsedSeconds = $test->started_at->diffInSeconds(now());
        $allowedSeconds = $quiz->total_duration * 60;

        return max(0, $allowedSeconds - $elapsedSeconds);
    }

    /**
     * Get user's attempt history for a quiz.
     */
    public function getAttemptHistory(User $user, Quiz $quiz): Collection
    {
        return Test::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at', 'desc')
            ->get();
    }

    /**
     * Get user's best attempt for a quiz.
     */
    public function getBestAttempt(User $user, Quiz $quiz): ?Test
    {
        return Test::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->orderBy('result', 'desc')
            ->first();
    }

    /**
     * Get user's latest attempt for a quiz.
     */
    public function getLatestAttempt(User $user, Quiz $quiz): ?Test
    {
        return Test::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at', 'desc')
            ->first();
    }

    /**
     * Get comprehensive quiz status for a user.
     *
     * Returns all information needed to display quiz cards in a list.
     */
    public function getQuizStatusForUser(User $user, Quiz $quiz): array
    {
        $attempts = Test::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->get();

        $inProgress = $attempts->whereNull('submitted_at')->first();
        $submittedAttempts = $attempts->whereNotNull('submitted_at');
        $lastSubmitted = $submittedAttempts->sortByDesc('submitted_at')->first();
        $bestAttempt = $submittedAttempts->sortByDesc('result')->first();
        $attemptsUsed = $submittedAttempts->count();

        $canAttempt = $this->canUserAttemptQuiz($user, $quiz);
        $remainingAttempts = $this->getRemainingAttempts($user, $quiz);

        // Calculate best score percentage
        $bestScorePercentage = null;
        if ($bestAttempt) {
            $totalQuestions = $quiz->questions()->count();
            if ($totalQuestions > 0) {
                $bestScorePercentage = round(($bestAttempt->result / $totalQuestions) * 100);
            }
        }

        // Calculate last score percentage
        $lastScorePercentage = null;
        if ($lastSubmitted) {
            $totalQuestions = $quiz->questions()->count();
            if ($totalQuestions > 0) {
                $lastScorePercentage = round(($lastSubmitted->result / $totalQuestions) * 100);
            }
        }

        return [
            'in_progress' => $inProgress,
            'last_submitted' => $lastSubmitted,
            'best_attempt' => $bestAttempt,
            'attempts_used' => $attemptsUsed,
            'attempts_allowed' => $quiz->attempts_allowed,
            'remaining_attempts' => $remainingAttempts,
            'can_attempt' => $canAttempt,
            'has_attempted' => $attemptsUsed > 0,
            'best_score_percentage' => $bestScorePercentage,
            'last_score_percentage' => $lastScorePercentage,
        ];
    }
}

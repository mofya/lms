<?php

namespace Tests\Feature\Services;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use App\Services\QuizTakingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTakingServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuizTakingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuizTakingService;
    }

    public function test_user_can_attempt_quiz_with_unlimited_attempts(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['attempts_allowed' => null]);

        $this->assertTrue($this->service->canUserAttemptQuiz($user, $quiz));
    }

    public function test_user_can_attempt_quiz_with_zero_attempts_means_unlimited(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['attempts_allowed' => 0]);

        $this->assertTrue($this->service->canUserAttemptQuiz($user, $quiz));
    }

    public function test_user_can_attempt_quiz_when_under_limit(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->withAttempts(3)->create();

        // Create 2 submitted attempts
        Test::factory()->count(2)->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->assertTrue($this->service->canUserAttemptQuiz($user, $quiz));
    }

    public function test_user_cannot_attempt_quiz_when_at_limit(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->withAttempts(2)->create();

        // Create 2 submitted attempts
        Test::factory()->count(2)->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->assertFalse($this->service->canUserAttemptQuiz($user, $quiz));
    }

    public function test_in_progress_attempts_do_not_count_toward_limit(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->withAttempts(2)->create();

        // Create 1 submitted and 1 in-progress attempt
        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);
        Test::factory()->inProgress()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->assertTrue($this->service->canUserAttemptQuiz($user, $quiz));
    }

    public function test_start_quiz_creates_new_attempt(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $test = $this->service->startQuiz($user, $quiz);

        $this->assertInstanceOf(Test::class, $test);
        $this->assertEquals($user->id, $test->user_id);
        $this->assertEquals($quiz->id, $test->quiz_id);
        $this->assertEquals(1, $test->attempt_number);
        $this->assertNotNull($test->started_at);
        $this->assertNull($test->submitted_at);
    }

    public function test_start_quiz_increments_attempt_number(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['attempts_allowed' => null]);

        // Create existing attempts
        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'attempt_number' => 1,
        ]);
        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'attempt_number' => 2,
        ]);

        $test = $this->service->startQuiz($user, $quiz);

        $this->assertEquals(3, $test->attempt_number);
    }

    public function test_start_quiz_throws_when_at_limit(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->withAttempts(1)->create();

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Maximum attempts reached');

        $this->service->startQuiz($user, $quiz);
    }

    public function test_get_in_progress_attempt_returns_existing(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $existingTest = Test::factory()->inProgress()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $result = $this->service->getInProgressAttempt($user, $quiz);

        $this->assertNotNull($result);
        $this->assertEquals($existingTest->id, $result->id);
    }

    public function test_get_in_progress_attempt_returns_null_when_none(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $result = $this->service->getInProgressAttempt($user, $quiz);

        $this->assertNull($result);
    }

    public function test_get_in_progress_attempt_ignores_submitted(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $result = $this->service->getInProgressAttempt($user, $quiz);

        $this->assertNull($result);
    }

    public function test_get_or_create_attempt_returns_existing(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $existingTest = Test::factory()->inProgress()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $result = $this->service->getOrCreateAttempt($user, $quiz);

        $this->assertEquals($existingTest->id, $result->id);
    }

    public function test_get_or_create_attempt_creates_new_when_none(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $result = $this->service->getOrCreateAttempt($user, $quiz);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals($quiz->id, $result->quiz_id);
    }

    public function test_prepare_quiz_questions_returns_questions(): void
    {
        $quiz = Quiz::factory()->create();
        $questions = Question::factory()->count(5)->create();
        $quiz->questions()->attach($questions);

        $result = $this->service->prepareQuizQuestions($quiz);

        $this->assertCount(5, $result);
    }

    public function test_prepare_quiz_questions_shuffles_when_enabled(): void
    {
        $quiz = Quiz::factory()->create(['shuffle_questions' => true]);
        $questions = Question::factory()->count(10)->create();
        $quiz->questions()->attach($questions);

        // Run multiple times to verify randomness (statistical test)
        $orders = [];
        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->prepareQuizQuestions($quiz);
            $orders[] = $result->pluck('id')->toArray();
        }

        // At least some orders should be different
        $this->assertTrue(count(array_unique($orders, SORT_REGULAR)) > 1);
    }

    public function test_save_answer_creates_new_answer(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->multipleChoice()->create();
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);
        $test = Test::factory()->inProgress()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $answer = $this->service->saveAnswer($test, $question->id, $option->id);

        $this->assertEquals($question->id, $answer->question_id);
        $this->assertEquals($option->id, $answer->option_id);
        $this->assertNull($answer->correct);
    }

    public function test_save_answer_updates_existing_answer(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->multipleChoice()->create();
        $option1 = QuestionOption::factory()->create(['question_id' => $question->id]);
        $option2 = QuestionOption::factory()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);
        $test = Test::factory()->inProgress()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->service->saveAnswer($test, $question->id, $option1->id);
        $answer = $this->service->saveAnswer($test, $question->id, $option2->id);

        $this->assertEquals($option2->id, $answer->option_id);
        $this->assertCount(1, $test->test_answers);
    }

    public function test_save_answer_throws_for_invalid_question(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->create(); // Not attached to quiz
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);
        $test = Test::factory()->inProgress()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Question does not belong to this quiz');

        $this->service->saveAnswer($test, $question->id, $option->id);
    }

    public function test_submit_quiz_calculates_score(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        // Create questions with correct options
        $question1 = Question::factory()->multipleChoice()->create();
        $correctOption1 = QuestionOption::factory()->create([
            'question_id' => $question1->id,
            'correct' => true,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $question1->id,
            'correct' => false,
        ]);

        $question2 = Question::factory()->multipleChoice()->create();
        $correctOption2 = QuestionOption::factory()->create([
            'question_id' => $question2->id,
            'correct' => true,
        ]);
        $wrongOption2 = QuestionOption::factory()->create([
            'question_id' => $question2->id,
            'correct' => false,
        ]);

        $quiz->questions()->attach([$question1->id, $question2->id]);

        $test = Test::factory()->inProgress()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        // Answer correctly for question 1, incorrectly for question 2
        $this->service->saveAnswer($test, $question1->id, $correctOption1->id);
        $this->service->saveAnswer($test, $question2->id, $wrongOption2->id);

        $result = $this->service->submitQuiz($test);

        $this->assertNotNull($result->submitted_at);
        $this->assertEquals(1, $result->correct_count);
        $this->assertEquals(1, $result->wrong_count);
    }

    public function test_submit_quiz_throws_when_already_submitted(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $test = Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already been submitted');

        $this->service->submitQuiz($test);
    }

    public function test_has_exceeded_time_limit(): void
    {
        $quiz = Quiz::factory()->withDuration(10)->create(); // 10 minute limit
        $test = Test::factory()->inProgress()->create([
            'quiz_id' => $quiz->id,
            'started_at' => now()->subMinutes(15), // Started 15 mins ago
        ]);

        $this->assertTrue($this->service->hasExceededTimeLimit($test));
    }

    public function test_has_not_exceeded_time_limit(): void
    {
        $quiz = Quiz::factory()->withDuration(30)->create(); // 30 minute limit
        $test = Test::factory()->inProgress()->create([
            'quiz_id' => $quiz->id,
            'started_at' => now()->subMinutes(15), // Started 15 mins ago
        ]);

        $this->assertFalse($this->service->hasExceededTimeLimit($test));
    }

    public function test_get_remaining_time_seconds(): void
    {
        $quiz = Quiz::factory()->withDuration(30)->create(); // 30 minute limit
        $test = Test::factory()->inProgress()->create([
            'quiz_id' => $quiz->id,
            'started_at' => now()->subMinutes(10), // Started 10 mins ago
        ]);

        $remaining = $this->service->getRemainingTimeSeconds($test);

        // Should be approximately 20 minutes = 1200 seconds
        $this->assertGreaterThan(1190, $remaining);
        $this->assertLessThanOrEqual(1200, $remaining);
    }

    public function test_get_remaining_time_returns_null_for_no_limit(): void
    {
        $quiz = Quiz::factory()->create(['total_duration' => null]);
        $test = Test::factory()->inProgress()->create([
            'quiz_id' => $quiz->id,
        ]);

        $this->assertNull($this->service->getRemainingTimeSeconds($test));
    }

    public function test_get_remaining_attempts(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->withAttempts(3)->create();

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $remaining = $this->service->getRemainingAttempts($user, $quiz);

        $this->assertEquals(2, $remaining);
    }

    public function test_get_remaining_attempts_unlimited(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['attempts_allowed' => null]);

        $remaining = $this->service->getRemainingAttempts($user, $quiz);

        $this->assertNull($remaining);
    }

    public function test_get_best_attempt(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['attempts_allowed' => null]);

        $lowScore = Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 5,
        ]);
        $highScore = Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 10,
        ]);

        $best = $this->service->getBestAttempt($user, $quiz);

        $this->assertEquals($highScore->id, $best->id);
    }

    public function test_get_latest_attempt(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['attempts_allowed' => null]);

        $older = Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now()->subDay(),
        ]);
        $newer = Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        $latest = $this->service->getLatestAttempt($user, $quiz);

        $this->assertEquals($newer->id, $latest->id);
    }

    public function test_get_quiz_status_for_user_with_no_attempts(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->withAttempts(3)->create();
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        $status = $this->service->getQuizStatusForUser($user, $quiz);

        $this->assertNull($status['in_progress']);
        $this->assertNull($status['last_submitted']);
        $this->assertNull($status['best_attempt']);
        $this->assertEquals(0, $status['attempts_used']);
        $this->assertEquals(3, $status['attempts_allowed']);
        $this->assertEquals(3, $status['remaining_attempts']);
        $this->assertTrue($status['can_attempt']);
        $this->assertFalse($status['has_attempted']);
        $this->assertNull($status['best_score_percentage']);
        $this->assertNull($status['last_score_percentage']);
    }

    public function test_get_quiz_status_for_user_with_in_progress_attempt(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->withAttempts(3)->create();
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        $inProgress = Test::factory()->inProgress()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $status = $this->service->getQuizStatusForUser($user, $quiz);

        $this->assertNotNull($status['in_progress']);
        $this->assertEquals($inProgress->id, $status['in_progress']->id);
        $this->assertTrue($status['can_attempt']);
        $this->assertFalse($status['has_attempted']);
        $this->assertEquals(0, $status['attempts_used']);
    }

    public function test_get_quiz_status_for_user_with_completed_attempts(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->withAttempts(3)->create();
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        // Create two completed attempts with different scores
        $lowScore = Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 2,
            'submitted_at' => now()->subHour(),
        ]);
        $highScore = Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 4,
            'submitted_at' => now(),
        ]);

        $status = $this->service->getQuizStatusForUser($user, $quiz);

        $this->assertNull($status['in_progress']);
        $this->assertEquals($highScore->id, $status['last_submitted']->id);
        $this->assertEquals($highScore->id, $status['best_attempt']->id);
        $this->assertEquals(2, $status['attempts_used']);
        $this->assertEquals(1, $status['remaining_attempts']);
        $this->assertTrue($status['can_attempt']);
        $this->assertTrue($status['has_attempted']);
        $this->assertEquals(80, $status['best_score_percentage']); // 4/5 = 80%
        $this->assertEquals(80, $status['last_score_percentage']); // 4/5 = 80%
    }

    public function test_get_quiz_status_for_user_at_attempt_limit(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->withAttempts(2)->create();
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        // Create max attempts
        Test::factory()->count(2)->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $status = $this->service->getQuizStatusForUser($user, $quiz);

        $this->assertFalse($status['can_attempt']);
        $this->assertEquals(0, $status['remaining_attempts']);
        $this->assertEquals(2, $status['attempts_used']);
    }

    public function test_get_quiz_status_for_user_with_unlimited_attempts(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['attempts_allowed' => null]);
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        Test::factory()->count(10)->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $status = $this->service->getQuizStatusForUser($user, $quiz);

        $this->assertTrue($status['can_attempt']);
        $this->assertNull($status['remaining_attempts']);
        $this->assertNull($status['attempts_allowed']);
        $this->assertEquals(10, $status['attempts_used']);
    }
}

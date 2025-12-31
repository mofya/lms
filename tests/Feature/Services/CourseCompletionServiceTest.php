<?php

namespace Tests\Feature\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use App\Services\CourseCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCompletionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CourseCompletionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CourseCompletionService::class);
    }

    public function test_has_passed_quiz_returns_true_for_any_submitted_attempt_without_passing_score(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'passing_score' => null,
        ]);
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
        ]);

        $this->assertTrue($this->service->hasPassedQuiz($user, $quiz));
    }

    public function test_has_passed_quiz_returns_false_when_no_submitted_attempt(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);

        $this->assertFalse($this->service->hasPassedQuiz($user, $quiz));
    }

    public function test_has_passed_quiz_checks_passing_score_requirement(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'passing_score' => 70,
        ]);
        Question::factory()->count(10)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 6,
        ]);

        $this->assertFalse($this->service->hasPassedQuiz($user, $quiz));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 8,
        ]);

        $this->assertTrue($this->service->hasPassedQuiz($user, $quiz));
    }

    public function test_has_completed_all_quizzes_returns_true_when_all_passed(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $quiz1 = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);
        $quiz2 = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);

        Question::factory()->count(5)->create()->each(fn ($q) => $quiz1->questions()->attach($q));
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz2->questions()->attach($q));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz1->id,
            'result' => 5,
        ]);
        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz2->id,
            'result' => 5,
        ]);

        $this->assertTrue($this->service->hasCompletedAllQuizzes($user, $course));
    }

    public function test_has_completed_all_quizzes_returns_false_when_one_not_passed(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $quiz1 = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);
        $quiz2 = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);

        Question::factory()->count(5)->create()->each(fn ($q) => $quiz1->questions()->attach($q));
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz2->questions()->attach($q));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz1->id,
            'result' => 5,
        ]);

        $this->assertFalse($this->service->hasCompletedAllQuizzes($user, $course));
    }

    public function test_check_and_award_certificate_generates_certificate_when_complete(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 5,
        ]);

        $certificate = $this->service->checkAndAwardCertificate($user, $course);

        $this->assertNotNull($certificate);
        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertEquals($user->id, $certificate->user_id);
        $this->assertEquals($course->id, $certificate->course_id);
    }

    public function test_check_and_award_certificate_returns_existing_certificate(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 5,
        ]);

        $certificate1 = $this->service->checkAndAwardCertificate($user, $course);
        $certificate2 = $this->service->checkAndAwardCertificate($user, $course);

        $this->assertEquals($certificate1->id, $certificate2->id);
        $this->assertEquals(1, Certificate::count());
    }

    public function test_check_and_award_certificate_returns_null_when_incomplete(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'passing_score' => 70,
        ]);
        Question::factory()->count(10)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'result' => 5,
        ]);

        $certificate = $this->service->checkAndAwardCertificate($user, $course);

        $this->assertNull($certificate);
        $this->assertEquals(0, Certificate::count());
    }

    public function test_get_course_quiz_progress_returns_correct_stats(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $quiz1 = Quiz::factory()->create(['course_id' => $course->id, 'is_published' => true]);
        $quiz2 = Quiz::factory()->create(['course_id' => $course->id, 'is_published' => true]);
        $quiz3 = Quiz::factory()->create(['course_id' => $course->id, 'is_published' => true]);

        Question::factory()->count(5)->create()->each(fn ($q) => $quiz1->questions()->attach($q));
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz2->questions()->attach($q));
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz3->questions()->attach($q));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz1->id,
            'result' => 5,
        ]);
        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz2->id,
            'result' => 5,
        ]);

        $progress = $this->service->getCourseQuizProgress($user, $course);

        $this->assertEquals(3, $progress['total_quizzes']);
        $this->assertEquals(2, $progress['passed_quizzes']);
        $this->assertEquals(67, $progress['percentage']);
    }

    public function test_only_required_quizzes_count_when_flag_is_set(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $requiredQuiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'require_passing_to_proceed' => true,
        ]);

        $optionalQuiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'require_passing_to_proceed' => false,
        ]);

        Question::factory()->count(5)->create()->each(fn ($q) => $requiredQuiz->questions()->attach($q));
        Question::factory()->count(5)->create()->each(fn ($q) => $optionalQuiz->questions()->attach($q));

        Test::factory()->submitted()->create([
            'user_id' => $user->id,
            'quiz_id' => $requiredQuiz->id,
            'result' => 5,
        ]);

        $this->assertTrue($this->service->hasCompletedAllQuizzes($user, $course));
    }

    public function test_observer_triggers_certificate_on_quiz_submission(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);
        Question::factory()->count(5)->create()->each(fn ($q) => $quiz->questions()->attach($q));

        $test = Test::factory()->inProgress()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->assertEquals(0, Certificate::count());

        $test->update([
            'submitted_at' => now(),
            'result' => 5,
            'correct_count' => 5,
            'wrong_count' => 0,
        ]);

        $this->assertEquals(1, Certificate::count());
        $certificate = Certificate::first();
        $this->assertEquals($user->id, $certificate->user_id);
        $this->assertEquals($course->id, $certificate->course_id);
    }
}

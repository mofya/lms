<?php

namespace Tests\Unit\Models;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Badge;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_badge_has_many_users(): void
    {
        $badge = Badge::factory()->create();
        $user = User::factory()->create();

        $badge->users()->attach($user, ['earned_at' => now()]);

        $this->assertTrue($badge->users->contains($user));
    }

    public function test_badge_has_many_user_badges(): void
    {
        $badge = Badge::factory()->create();
        $userBadge = UserBadge::factory()->create(['badge_id' => $badge->id]);

        $this->assertTrue($badge->userBadges->contains($userBadge));
    }

    public function test_check_and_award_returns_false_when_inactive(): void
    {
        $badge = Badge::factory()->inactive()->create();
        $user = User::factory()->create();

        $this->assertFalse($badge->checkAndAward($user));
    }

    public function test_check_and_award_returns_false_when_already_earned(): void
    {
        $badge = Badge::factory()->forFirstQuiz()->create();
        $user = User::factory()->create();

        $badge->users()->attach($user, ['earned_at' => now()]);

        $this->assertFalse($badge->checkAndAward($user));
    }

    public function test_check_and_award_first_quiz_badge(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        // Create badge AFTER the test so observer doesn't auto-award
        Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);

        $badge = Badge::factory()->forFirstQuiz()->create();

        $this->assertTrue($badge->checkAndAward($user));
        $this->assertTrue($user->badges()->where('badge_id', $badge->id)->exists());
    }

    public function test_check_and_award_first_assignment_badge(): void
    {
        $user = User::factory()->create();

        // Create submission BEFORE badge so observer doesn't auto-award
        AssignmentSubmission::factory()->submitted()->create(['user_id' => $user->id]);

        $badge = Badge::factory()->forFirstAssignment()->create();

        $this->assertTrue($badge->checkAndAward($user));
    }

    public function test_check_and_award_perfect_score_badge(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->multipleChoice()->create();
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'option_id' => $correctOption->id,
        ]);

        // Create badge AFTER test so observer doesn't auto-award
        $badge = Badge::factory()->forPerfectScore()->create();

        $this->assertTrue($badge->checkAndAward($user));
    }

    public function test_check_and_award_quizzes_completed_badge(): void
    {
        $user = User::factory()->create();

        $quiz1 = Quiz::factory()->create();
        $quiz2 = Quiz::factory()->create();

        Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz1->id]);
        Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz2->id]);

        // Create badge AFTER tests so observer doesn't auto-award
        $badge = Badge::factory()->forQuizzesCompleted(2)->create();

        $this->assertTrue($badge->checkAndAward($user));
    }

    public function test_check_and_award_quizzes_completed_returns_false_when_not_enough(): void
    {
        $badge = Badge::factory()->forQuizzesCompleted(3)->create();
        $user = User::factory()->create();

        $quiz = Quiz::factory()->create();
        Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);

        $this->assertFalse($badge->checkAndAward($user));
    }

    public function test_check_and_award_assignments_completed_badge(): void
    {
        $user = User::factory()->create();

        $assignment1 = Assignment::factory()->create();
        $assignment2 = Assignment::factory()->create();

        AssignmentSubmission::factory()->submitted()->create([
            'user_id' => $user->id,
            'assignment_id' => $assignment1->id,
        ]);
        AssignmentSubmission::factory()->submitted()->create([
            'user_id' => $user->id,
            'assignment_id' => $assignment2->id,
        ]);

        // Create badge AFTER submissions so observer doesn't auto-award
        $badge = Badge::factory()->forAssignmentsCompleted(2)->create();

        $this->assertTrue($badge->checkAndAward($user));
    }

    public function test_check_and_award_course_completed_badge(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $lesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $course->students()->attach($user);
        $user->completedLessons()->attach($lesson);

        // Create badge AFTER course completion so observer doesn't auto-award
        $badge = Badge::factory()->forCourseCompleted()->create();

        $this->assertTrue($badge->checkAndAward($user));
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\SubmissionGrade;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $grade = Grade::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($grade->user->is($user));
    }

    public function test_grade_belongs_to_course(): void
    {
        $course = Course::factory()->create();
        $grade = Grade::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($grade->course->is($course));
    }

    public function test_get_or_create_creates_new_grade(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $grade = Grade::getOrCreateForUserCourse($user, $course);

        $this->assertDatabaseHas('grades', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
        $this->assertEquals(40, $grade->quiz_weight);
        $this->assertEquals(50, $grade->assignment_weight);
        $this->assertEquals(10, $grade->participation_weight);
    }

    public function test_get_or_create_returns_existing_grade(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $existingGrade = Grade::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'quiz_average' => 95,
        ]);

        $grade = Grade::getOrCreateForUserCourse($user, $course);

        $this->assertTrue($grade->is($existingGrade));
        $this->assertEquals(95, $grade->quiz_average);
    }

    public function test_recalculate_updates_quiz_average(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $quiz = Quiz::factory()->published()->create(['course_id' => $course->id]);

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

        // Use getOrCreateForUserCourse since Test observer may have created a grade
        $grade = Grade::getOrCreateForUserCourse($user, $course);

        $grade->recalculate();

        $this->assertEquals(100, $grade->fresh()->quiz_average);
    }

    public function test_recalculate_updates_participation_score(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        $lesson1 = Lesson::factory()->published()->create(['course_id' => $course->id]);
        Lesson::factory()->published()->create(['course_id' => $course->id]);

        $user->completedLessons()->attach($lesson1);

        // Use getOrCreateForUserCourse as it's more reliable
        $grade = Grade::getOrCreateForUserCourse($user, $course);

        $grade->recalculate();

        $this->assertEquals(50, $grade->fresh()->participation_score);
    }

    public function test_recalculate_updates_assignment_average(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $assignment = Assignment::factory()->published()->create([
            'course_id' => $course->id,
            'max_points' => 100,
        ]);

        $submission = AssignmentSubmission::factory()->submitted()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
        ]);

        SubmissionGrade::factory()->approved(80)->create([
            'submission_id' => $submission->id,
        ]);

        // Use getOrCreateForUserCourse since observer may have created a grade
        $grade = Grade::getOrCreateForUserCourse($user, $course);

        $grade->recalculate();

        $this->assertEquals(80, $grade->fresh()->assignment_average);
    }

    public function test_recalculate_calculates_weighted_final_grade(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        // Create a quiz with 100% score
        $quiz = Quiz::factory()->published()->create(['course_id' => $course->id]);
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

        // Create an assignment with 80% score
        $assignment = Assignment::factory()->published()->create([
            'course_id' => $course->id,
            'max_points' => 100,
        ]);

        $submission = AssignmentSubmission::factory()->submitted()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
        ]);

        SubmissionGrade::factory()->approved(80)->create([
            'submission_id' => $submission->id,
        ]);

        // Complete 1 of 2 lessons (50% participation)
        $lesson1 = Lesson::factory()->published()->create(['course_id' => $course->id]);
        Lesson::factory()->published()->create(['course_id' => $course->id]);
        $user->completedLessons()->attach($lesson1);

        // Use getOrCreateForUserCourse since Test observer may have created a grade
        $grade = Grade::getOrCreateForUserCourse($user, $course);
        $grade->update([
            'quiz_weight' => 40,
            'assignment_weight' => 50,
            'participation_weight' => 10,
        ]);

        $grade->recalculate();

        $grade->refresh();

        // Expected: (100 * 0.4) + (80 * 0.5) + (50 * 0.1) = 40 + 40 + 5 = 85
        $this->assertEquals(85, $grade->final_grade);
    }
}

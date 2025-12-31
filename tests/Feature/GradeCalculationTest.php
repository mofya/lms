<?php

namespace Tests\Feature;

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

class GradeCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->course = Course::factory()->published()->create();
    }

    public function test_grade_calculation_with_only_quizzes(): void
    {
        // Create a quiz with 2 questions, answer both correctly (100%)
        $quiz = Quiz::factory()->published()->create(['course_id' => $this->course->id]);

        $question1 = Question::factory()->multipleChoice()->create();
        $correct1 = QuestionOption::factory()->correct()->create(['question_id' => $question1->id]);

        $question2 = Question::factory()->multipleChoice()->create();
        $correct2 = QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);

        $quiz->questions()->attach([$question1->id, $question2->id]);

        $test = Test::factory()->create([
            'user_id' => $this->user->id,
            'quiz_id' => $quiz->id,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $this->user->id,
            'test_id' => $test->id,
            'question_id' => $question1->id,
            'option_id' => $correct1->id,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $this->user->id,
            'test_id' => $test->id,
            'question_id' => $question2->id,
            'option_id' => $correct2->id,
        ]);

        // Use getOrCreateForUserCourse since Test observer may have created a grade
        $grade = Grade::getOrCreateForUserCourse($this->user, $this->course);

        $grade->recalculate();
        $grade->refresh();

        $this->assertEquals(100, $grade->quiz_average);
        $this->assertEquals(1, $grade->completed_quizzes);
    }

    public function test_grade_calculation_with_only_assignments(): void
    {
        $assignment = Assignment::factory()->published()->create([
            'course_id' => $this->course->id,
            'max_points' => 100,
        ]);

        $submission = AssignmentSubmission::factory()->submitted()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->user->id,
        ]);

        SubmissionGrade::factory()->approved(80)->create([
            'submission_id' => $submission->id,
        ]);

        // Use getOrCreateForUserCourse since observer may have created a grade
        $grade = Grade::getOrCreateForUserCourse($this->user, $this->course);

        $grade->recalculate();
        $grade->refresh();

        $this->assertEquals(80, $grade->assignment_average);
        $this->assertEquals(1, $grade->completed_assignments);
    }

    public function test_grade_calculation_with_only_participation(): void
    {
        Lesson::factory()->published()->create(['course_id' => $this->course->id]);
        $lesson2 = Lesson::factory()->published()->create(['course_id' => $this->course->id]);

        // Complete 1 of 2 lessons
        $this->user->completedLessons()->attach($lesson2);

        // Use getOrCreateForUserCourse since observer may have created a grade
        $grade = Grade::getOrCreateForUserCourse($this->user, $this->course);

        $grade->recalculate();
        $grade->refresh();

        $this->assertEquals(50, $grade->participation_score);
    }

    public function test_grade_calculation_with_all_components(): void
    {
        // Quiz: 100%
        $quiz = Quiz::factory()->published()->create(['course_id' => $this->course->id]);
        $question = Question::factory()->multipleChoice()->create();
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        $test = Test::factory()->create([
            'user_id' => $this->user->id,
            'quiz_id' => $quiz->id,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $this->user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'option_id' => $correctOption->id,
        ]);

        // Assignment: 80%
        $assignment = Assignment::factory()->published()->create([
            'course_id' => $this->course->id,
            'max_points' => 100,
        ]);

        $submission = AssignmentSubmission::factory()->submitted()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->user->id,
        ]);

        SubmissionGrade::factory()->approved(80)->create([
            'submission_id' => $submission->id,
        ]);

        // Participation: 100% (complete all lessons)
        $lesson = Lesson::factory()->published()->create(['course_id' => $this->course->id]);
        $this->user->completedLessons()->attach($lesson);

        // Use getOrCreateForUserCourse since Test observer may have created a grade
        $grade = Grade::getOrCreateForUserCourse($this->user, $this->course);
        $grade->update([
            'quiz_weight' => 40,
            'assignment_weight' => 50,
            'participation_weight' => 10,
        ]);

        $grade->recalculate();
        $grade->refresh();

        // Expected: (100 * 0.4) + (80 * 0.5) + (100 * 0.1) = 40 + 40 + 10 = 90
        $this->assertEquals(100, $grade->quiz_average);
        $this->assertEquals(80, $grade->assignment_average);
        $this->assertEquals(100, $grade->participation_score);
        $this->assertEquals(90, $grade->final_grade);
    }

    public function test_best_quiz_attempt_is_used(): void
    {
        $quiz = Quiz::factory()->published()->create(['course_id' => $this->course->id]);

        $question = Question::factory()->multipleChoice()->create();
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $wrongOption = QuestionOption::factory()->create(['question_id' => $question->id, 'correct' => false]);
        $quiz->questions()->attach($question);

        // First attempt: wrong answer (0%)
        $test1 = Test::factory()->create([
            'user_id' => $this->user->id,
            'quiz_id' => $quiz->id,
            'attempt_number' => 1,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $this->user->id,
            'test_id' => $test1->id,
            'question_id' => $question->id,
            'option_id' => $wrongOption->id,
        ]);

        // Second attempt: correct answer (100%)
        $test2 = Test::factory()->create([
            'user_id' => $this->user->id,
            'quiz_id' => $quiz->id,
            'attempt_number' => 2,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $this->user->id,
            'test_id' => $test2->id,
            'question_id' => $question->id,
            'option_id' => $correctOption->id,
        ]);

        // Use getOrCreateForUserCourse since Test observer may have created a grade
        $grade = Grade::getOrCreateForUserCourse($this->user, $this->course);

        $grade->recalculate();
        $grade->refresh();

        // Should use the best score (100%)
        $this->assertEquals(100, $grade->quiz_average);
    }

    public function test_best_assignment_submission_is_used(): void
    {
        $assignment = Assignment::factory()->published()->create([
            'course_id' => $this->course->id,
            'max_points' => 100,
            'max_submissions' => 2,
        ]);

        // First submission: 70%
        $submission1 = AssignmentSubmission::factory()->submitted()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->user->id,
            'attempt_number' => 1,
        ]);

        SubmissionGrade::factory()->approved(70)->create([
            'submission_id' => $submission1->id,
        ]);

        // Second submission: 90%
        $submission2 = AssignmentSubmission::factory()->submitted()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->user->id,
            'attempt_number' => 2,
        ]);

        SubmissionGrade::factory()->approved(90)->create([
            'submission_id' => $submission2->id,
        ]);

        // Use getOrCreateForUserCourse since observer may have created a grade
        $grade = Grade::getOrCreateForUserCourse($this->user, $this->course);

        $grade->recalculate();
        $grade->refresh();

        // Should use the best score (90%)
        $this->assertEquals(90, $grade->assignment_average);
    }
}

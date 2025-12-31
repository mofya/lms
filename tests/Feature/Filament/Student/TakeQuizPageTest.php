<?php

namespace Tests\Feature\Filament\Student;

use App\Enums\NavigatorPosition;
use App\Filament\Student\Pages\TakeQuiz;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TakeQuizPageTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    private Quiz $quiz;

    private Question $question;

    private QuestionOption $correctOption;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('student'));

        $this->student = User::factory()->create();
        $this->course = Course::factory()->published()->create();
        $this->course->students()->attach($this->student);

        $this->quiz = Quiz::factory()->published()->create([
            'course_id' => $this->course->id,
            'attempts_allowed' => 3,
            'show_one_question_at_a_time' => true,
            'navigator_position' => NavigatorPosition::Bottom,
            'allow_question_navigation' => true,
            'show_progress_bar' => true,
        ]);

        $this->question = Question::factory()->multipleChoice()->create();
        $this->correctOption = QuestionOption::factory()->correct()->create(['question_id' => $this->question->id]);
        QuestionOption::factory()->create(['question_id' => $this->question->id, 'correct' => false]);
        $this->quiz->questions()->attach($this->question);
    }

    public function test_enrolled_student_can_access_quiz(): void
    {
        $this->actingAs($this->student);

        Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id])
            ->assertStatus(200)
            ->assertSee($this->quiz->title)
            ->assertSee($this->question->question_text);
    }

    public function test_unenrolled_student_cannot_access_quiz(): void
    {
        $otherStudent = User::factory()->create();
        $this->actingAs($otherStudent);

        Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id])
            ->assertStatus(403);
    }

    public function test_unpublished_quiz_returns_404(): void
    {
        $unpublishedQuiz = Quiz::factory()->create([
            'course_id' => $this->course->id,
            'is_published' => false,
        ]);

        $this->actingAs($this->student);

        Livewire::test(TakeQuiz::class, ['record' => $unpublishedQuiz->id])
            ->assertStatus(404);
    }

    public function test_student_cannot_exceed_max_attempts(): void
    {
        $this->actingAs($this->student);

        // Create submitted attempts (only submitted attempts count toward limit)
        for ($i = 0; $i < $this->quiz->attempts_allowed; $i++) {
            Test::factory()->submitted()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
                'attempt_number' => $i + 1,
            ]);
        }

        Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id])
            ->assertStatus(403);
    }

    public function test_can_navigate_to_next_question(): void
    {
        $question2 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        $this->quiz->questions()->attach($question2);

        $this->actingAs($this->student);

        Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id])
            ->assertSet('currentQuestionIndex', 0)
            ->call('changeQuestion')
            ->assertSet('currentQuestionIndex', 1);
    }

    public function test_can_navigate_to_previous_question(): void
    {
        $question2 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        $this->quiz->questions()->attach($question2);

        $this->actingAs($this->student);

        Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id])
            ->call('changeQuestion')
            ->assertSet('currentQuestionIndex', 1)
            ->call('previousQuestion')
            ->assertSet('currentQuestionIndex', 0);
    }

    public function test_go_to_question_works(): void
    {
        $question2 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        $this->quiz->questions()->attach($question2);

        $this->actingAs($this->student);

        Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id])
            ->assertSet('currentQuestionIndex', 0)
            ->call('goToQuestion', 1)
            ->assertSet('currentQuestionIndex', 1);
    }

    public function test_navigation_disabled_when_not_allowed(): void
    {
        $this->quiz->update(['allow_question_navigation' => false]);

        $question2 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        $this->quiz->questions()->attach($question2);

        $this->actingAs($this->student);

        Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id])
            ->call('changeQuestion')
            ->assertSet('currentQuestionIndex', 1)
            ->call('previousQuestion')
            ->assertSet('currentQuestionIndex', 1)
            ->call('goToQuestion', 0)
            ->assertSet('currentQuestionIndex', 1);
    }

    public function test_submit_creates_test_and_answers(): void
    {
        $this->actingAs($this->student);

        Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id])
            ->set('questionsAnswers.0', $this->correctOption->id)
            ->call('submit')
            ->assertRedirect();

        $this->assertDatabaseHas('tests', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'result' => 1,
        ]);

        $this->assertDatabaseHas('test_answers', [
            'user_id' => $this->student->id,
            'question_id' => $this->question->id,
            'option_id' => $this->correctOption->id,
            'correct' => true,
        ]);
    }

    public function test_progress_percentage_is_calculated(): void
    {
        $question2 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        $this->quiz->questions()->attach($question2);

        $this->actingAs($this->student);

        $component = Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id]);

        $this->assertEquals(0, $component->get('progressPercentage'));

        $component->set('questionsAnswers.0', $this->correctOption->id);
        $this->assertEquals(50, $component->get('progressPercentage'));
    }

    public function test_is_question_answered_returns_correct_value(): void
    {
        $this->actingAs($this->student);

        $component = Livewire::test(TakeQuiz::class, ['record' => $this->quiz->id]);

        $this->assertFalse($component->instance()->isQuestionAnswered(0));

        $component->set('questionsAnswers.0', $this->correctOption->id);

        $this->assertTrue($component->instance()->isQuestionAnswered(0));
    }
}

<?php

namespace Tests\Feature\Filament\Student;

use App\Filament\Student\Resources\StudentResultResource\Pages\ViewStudentResult;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewStudentResultPageTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Quiz $quiz;

    private Test $test;

    private Question $question;

    private QuestionOption $correctOption;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('student'));

        $this->student = User::factory()->create();

        $this->quiz = Quiz::factory()->published()->create([
            'attempts_allowed' => 3,
        ]);

        $this->question = Question::factory()->multipleChoice()->create();
        $this->correctOption = QuestionOption::factory()->correct()->create(['question_id' => $this->question->id]);
        QuestionOption::factory()->create(['question_id' => $this->question->id, 'correct' => false]);
        $this->quiz->questions()->attach($this->question);

        $this->test = Test::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'result' => 1,
            'time_spent' => 180,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $this->student->id,
            'test_id' => $this->test->id,
            'question_id' => $this->question->id,
            'option_id' => $this->correctOption->id,
            'correct' => true,
        ]);
    }

    public function test_student_can_view_their_result(): void
    {
        $this->actingAs($this->student);

        Livewire::test(ViewStudentResult::class, ['record' => $this->test->id])
            ->assertStatus(200)
            ->assertSee('Quiz Results')
            ->assertSee($this->quiz->title);
    }

    public function test_shows_correct_score_percentage(): void
    {
        $this->actingAs($this->student);

        $component = Livewire::test(ViewStudentResult::class, ['record' => $this->test->id]);

        $this->assertEquals(100, $component->instance()->getScorePercentage());
    }

    public function test_shows_correct_and_wrong_counts(): void
    {
        $question2 = Question::factory()->multipleChoice()->create();
        $wrongOption = QuestionOption::factory()->create(['question_id' => $question2->id, 'correct' => false]);
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        $this->quiz->questions()->attach($question2);

        TestAnswer::factory()->create([
            'user_id' => $this->student->id,
            'test_id' => $this->test->id,
            'question_id' => $question2->id,
            'option_id' => $wrongOption->id,
            'correct' => false,
        ]);

        $this->test->update(['result' => 1]);

        $this->actingAs($this->student);

        $component = Livewire::test(ViewStudentResult::class, ['record' => $this->test->id]);

        $this->assertEquals(1, $component->instance()->getCorrectCount());
        $this->assertEquals(1, $component->instance()->getWrongCount());
    }

    public function test_is_passed_returns_true_for_70_percent_or_above(): void
    {
        $this->actingAs($this->student);

        $component = Livewire::test(ViewStudentResult::class, ['record' => $this->test->id]);

        $this->assertTrue($component->instance()->isPassed());
    }

    public function test_is_passed_returns_false_for_below_70_percent(): void
    {
        $question2 = Question::factory()->multipleChoice()->create();
        $question3 = Question::factory()->multipleChoice()->create();
        $wrongOption2 = QuestionOption::factory()->create(['question_id' => $question2->id, 'correct' => false]);
        $wrongOption3 = QuestionOption::factory()->create(['question_id' => $question3->id, 'correct' => false]);
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        QuestionOption::factory()->correct()->create(['question_id' => $question3->id]);
        $this->quiz->questions()->attach([$question2->id, $question3->id]);

        TestAnswer::factory()->create([
            'user_id' => $this->student->id,
            'test_id' => $this->test->id,
            'question_id' => $question2->id,
            'option_id' => $wrongOption2->id,
            'correct' => false,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $this->student->id,
            'test_id' => $this->test->id,
            'question_id' => $question3->id,
            'option_id' => $wrongOption3->id,
            'correct' => false,
        ]);

        $this->actingAs($this->student);

        $component = Livewire::test(ViewStudentResult::class, ['record' => $this->test->id]);

        $this->assertFalse($component->instance()->isPassed());
    }

    public function test_formatted_time_displays_correctly(): void
    {
        $this->actingAs($this->student);

        $component = Livewire::test(ViewStudentResult::class, ['record' => $this->test->id]);

        $this->assertEquals('3:00', $component->instance()->getFormattedTime());
    }

    public function test_can_retake_quiz_when_attempts_remaining(): void
    {
        $this->actingAs($this->student);

        $component = Livewire::test(ViewStudentResult::class, ['record' => $this->test->id]);

        $this->assertTrue($component->instance()->canRetakeQuiz());
    }

    public function test_cannot_retake_quiz_when_no_attempts_remaining(): void
    {
        for ($i = 0; $i < 2; $i++) {
            Test::factory()->create([
                'user_id' => $this->student->id,
                'quiz_id' => $this->quiz->id,
            ]);
        }

        $this->actingAs($this->student);

        $component = Livewire::test(ViewStudentResult::class, ['record' => $this->test->id]);

        $this->assertFalse($component->instance()->canRetakeQuiz());
    }

    public function test_retake_quiz_redirects(): void
    {
        $this->actingAs($this->student);

        Livewire::test(ViewStudentResult::class, ['record' => $this->test->id])
            ->call('retakeQuiz')
            ->assertRedirect();
    }
}

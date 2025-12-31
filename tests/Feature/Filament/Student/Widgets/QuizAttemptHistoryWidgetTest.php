<?php

namespace Tests\Feature\Filament\Student\Widgets;

use App\Filament\Student\Widgets\QuizAttemptHistoryWidget;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizAttemptHistoryWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('student'));
    }

    public function test_widget_displays_user_attempts(): void
    {
        $student = User::factory()->create();
        $quiz = Quiz::factory()->create(['title' => 'History Quiz']);

        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
            'time_spent' => 120,
        ]);

        $this->actingAs($student);

        Livewire::test(QuizAttemptHistoryWidget::class)
            ->assertSee('History Quiz')
            ->assertSee('100%')
            ->assertSee('Passed')
            ->assertSee('2:00');
    }

    public function test_widget_shows_failed_status(): void
    {
        $student = User::factory()->create();
        $quiz = Quiz::factory()->create(['title' => 'Hard Quiz']);

        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 0,
        ]);

        $this->actingAs($student);

        Livewire::test(QuizAttemptHistoryWidget::class)
            ->assertSee('Hard Quiz')
            ->assertSee('0%')
            ->assertSee('Failed');
    }

    public function test_widget_only_shows_current_user_attempts(): void
    {
        $student1 = User::factory()->create();
        $student2 = User::factory()->create();

        $quiz = Quiz::factory()->create(['title' => 'Shared Quiz']);
        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        Test::factory()->create([
            'user_id' => $student1->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
        ]);

        $otherQuiz = Quiz::factory()->create(['title' => 'Other Quiz']);
        $otherQuiz->questions()->attach($question);
        Test::factory()->create([
            'user_id' => $student2->id,
            'quiz_id' => $otherQuiz->id,
            'result' => 1,
        ]);

        $this->actingAs($student1);

        Livewire::test(QuizAttemptHistoryWidget::class)
            ->assertSee('Shared Quiz')
            ->assertDontSee('Other Quiz');
    }

    public function test_widget_shows_empty_when_no_attempts(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(QuizAttemptHistoryWidget::class)
            ->assertSee('Recent Quiz Attempts');
    }
}

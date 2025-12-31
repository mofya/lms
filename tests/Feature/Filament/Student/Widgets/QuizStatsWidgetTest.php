<?php

namespace Tests\Feature\Filament\Student\Widgets;

use App\Filament\Student\Widgets\QuizStatsWidget;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_displays_stats_for_authenticated_user(): void
    {
        $student = User::factory()->create();
        $quiz1 = Quiz::factory()->create();
        $quiz2 = Quiz::factory()->create();

        $question1 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question1->id]);
        $quiz1->questions()->attach($question1);

        $question2 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        $quiz2->questions()->attach($question2);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz1->id,
            'result' => 1,
        ]);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz2->id,
            'result' => 0,
        ]);

        $this->actingAs($student);

        Livewire::test(QuizStatsWidget::class)
            ->assertSee('Quizzes Taken')
            ->assertSee('Total Attempts')
            ->assertSee('Average Score')
            ->assertSee('Pass Rate')
            ->assertSee('2');
    }

    public function test_widget_shows_zero_stats_when_no_attempts(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(QuizStatsWidget::class)
            ->assertSee('0')
            ->assertSee('Quizzes Taken');
    }

    public function test_widget_calculates_pass_rate_correctly(): void
    {
        $student = User::factory()->create();

        $quiz = Quiz::factory()->create();
        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
        ]);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 0,
        ]);

        $this->actingAs($student);

        Livewire::test(QuizStatsWidget::class)
            ->assertSee('50%');
    }

    public function test_widget_only_shows_current_user_stats(): void
    {
        $student1 = User::factory()->create();
        $student2 = User::factory()->create();

        $quiz = Quiz::factory()->create();
        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        Test::factory()->create([
            'user_id' => $student1->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
        ]);

        Test::factory()->create([
            'user_id' => $student2->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
        ]);

        $this->actingAs($student1);

        Livewire::test(QuizStatsWidget::class)
            ->assertSee('1');
    }
}

<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\RecentQuizAttemptsWidget;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecentQuizAttemptsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_displays_recent_attempts(): void
    {
        $student = User::factory()->create(['name' => 'Test Student']);
        $quiz = Quiz::factory()->create(['title' => 'Sample Quiz']);

        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
        ]);

        Livewire::test(RecentQuizAttemptsWidget::class)
            ->assertSee('Test Student')
            ->assertSee('Sample Quiz')
            ->assertSee('100%')
            ->assertSee('Passed');
    }

    public function test_widget_shows_failed_status_for_low_scores(): void
    {
        $student = User::factory()->create(['name' => 'Failing Student']);
        $quiz = Quiz::factory()->create(['title' => 'Hard Quiz']);

        $question1 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question1->id]);

        $question2 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);

        $quiz->questions()->attach([$question1->id, $question2->id]);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 0,
        ]);

        Livewire::test(RecentQuizAttemptsWidget::class)
            ->assertSee('Failing Student')
            ->assertSee('Hard Quiz')
            ->assertSee('0%')
            ->assertSee('Failed');
    }

    public function test_widget_shows_empty_state_when_no_attempts(): void
    {
        Livewire::test(RecentQuizAttemptsWidget::class)
            ->assertSee('Recent Quiz Attempts');
    }

    public function test_widget_orders_by_most_recent(): void
    {
        $student = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        $olderTest = Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 0,
            'created_at' => now()->subDays(2),
        ]);

        $newerTest = Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
            'created_at' => now(),
        ]);

        Livewire::test(RecentQuizAttemptsWidget::class)
            ->assertSeeInOrder(['100%', '0%']);
    }
}

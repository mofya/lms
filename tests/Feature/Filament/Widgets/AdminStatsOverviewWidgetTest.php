<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\AdminStatsOverviewWidget;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminStatsOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_displays_correct_stats(): void
    {
        $course = Course::factory()->published()->create();

        $student1 = User::factory()->create();
        $student2 = User::factory()->create();
        $course->students()->attach([$student1->id, $student2->id]);

        $quiz = Quiz::factory()->published()->create(['course_id' => $course->id]);

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
            'result' => 0,
        ]);

        Livewire::test(AdminStatsOverviewWidget::class)
            ->assertSee('2')
            ->assertSee('1')
            ->assertSee('Total Students')
            ->assertSee('Total Quizzes')
            ->assertSee('Total Attempts')
            ->assertSee('Average Score');
    }

    public function test_widget_shows_zero_stats_when_no_data(): void
    {
        Livewire::test(AdminStatsOverviewWidget::class)
            ->assertSee('0')
            ->assertSee('Total Students')
            ->assertSee('Total Quizzes')
            ->assertSee('Total Attempts');
    }
}

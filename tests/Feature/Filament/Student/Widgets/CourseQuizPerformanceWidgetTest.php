<?php

namespace Tests\Feature\Filament\Student\Widgets;

use App\Filament\Student\Widgets\CourseQuizPerformanceWidget;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseQuizPerformanceWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_displays_course_performance(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create(['title' => 'PHP Course']);

        $quiz = Quiz::factory()->create(['course_id' => $course->id]);
        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
        ]);

        $this->actingAs($student);

        Livewire::test(CourseQuizPerformanceWidget::class)
            ->assertSee('PHP Course')
            ->assertSee('Performance by Course');
    }

    public function test_widget_shows_multiple_courses(): void
    {
        $student = User::factory()->create();

        $course1 = Course::factory()->published()->create(['title' => 'Laravel Course']);
        $course2 = Course::factory()->published()->create(['title' => 'Vue Course']);

        $quiz1 = Quiz::factory()->create(['course_id' => $course1->id]);
        $quiz2 = Quiz::factory()->create(['course_id' => $course2->id]);

        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz1->questions()->attach($question);
        $quiz2->questions()->attach($question);

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

        Livewire::test(CourseQuizPerformanceWidget::class)
            ->assertSee('Laravel Course')
            ->assertSee('Vue Course');
    }

    public function test_widget_only_shows_courses_with_attempts(): void
    {
        $student = User::factory()->create();

        $courseWithAttempts = Course::factory()->published()->create(['title' => 'Active Course']);
        $courseWithoutAttempts = Course::factory()->published()->create(['title' => 'Unused Course']);

        $quiz = Quiz::factory()->create(['course_id' => $courseWithAttempts->id]);
        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        Test::factory()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'result' => 1,
        ]);

        $this->actingAs($student);

        Livewire::test(CourseQuizPerformanceWidget::class)
            ->assertSee('Active Course')
            ->assertDontSee('Unused Course');
    }

    public function test_widget_shows_empty_when_no_attempts(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(CourseQuizPerformanceWidget::class)
            ->assertSee('Performance by Course');
    }
}

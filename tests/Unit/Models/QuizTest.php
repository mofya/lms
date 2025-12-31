<?php

namespace Tests\Unit\Models;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_belongs_to_course(): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($quiz->course->is($course));
    }

    public function test_quiz_has_many_questions(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->create();

        $quiz->questions()->attach($question);

        $this->assertTrue($quiz->questions->contains($question));
    }

    public function test_quiz_has_many_tests(): void
    {
        $quiz = Quiz::factory()->create();
        $test = Test::factory()->create(['quiz_id' => $quiz->id]);

        $this->assertTrue($quiz->tests->contains($test));
    }

    public function test_published_scope_filters_correctly(): void
    {
        Quiz::factory()->create(['is_published' => false]);
        $publishedQuiz = Quiz::factory()->published()->create();

        $published = Quiz::published()->get();

        $this->assertCount(1, $published);
        $this->assertTrue($published->contains($publishedQuiz));
    }

    public function test_is_active_returns_true_when_within_time_window(): void
    {
        $quiz = Quiz::factory()->active()->create();

        $this->assertTrue($quiz->isActive());
    }

    public function test_is_active_returns_false_before_start_time(): void
    {
        $quiz = Quiz::factory()->create([
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);

        $this->assertFalse($quiz->isActive());
    }

    public function test_is_active_returns_false_after_end_time(): void
    {
        $quiz = Quiz::factory()->create([
            'start_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
        ]);

        $this->assertFalse($quiz->isActive());
    }

    public function test_is_active_returns_false_when_no_time_set(): void
    {
        $quiz = Quiz::factory()->create([
            'start_time' => null,
            'end_time' => null,
        ]);

        $this->assertFalse($quiz->isActive());
    }

    public function test_should_use_total_duration_when_set(): void
    {
        $quiz = Quiz::factory()->withDuration(30)->create();

        $this->assertTrue($quiz->shouldUseTotalDuration());
    }

    public function test_should_not_use_total_duration_when_not_set(): void
    {
        $quiz = Quiz::factory()->create(['total_duration' => null]);

        $this->assertFalse($quiz->shouldUseTotalDuration());
    }

    public function test_should_not_use_total_duration_when_zero(): void
    {
        $quiz = Quiz::factory()->create(['total_duration' => 0]);

        $this->assertFalse($quiz->shouldUseTotalDuration());
    }
}

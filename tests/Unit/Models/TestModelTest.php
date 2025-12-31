<?php

namespace Tests\Unit\Models;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $test = Test::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($test->user->is($user));
    }

    public function test_test_belongs_to_quiz(): void
    {
        $quiz = Quiz::factory()->create();
        $test = Test::factory()->create(['quiz_id' => $quiz->id]);

        $this->assertTrue($test->quiz->is($quiz));
    }

    public function test_test_has_many_test_answers(): void
    {
        $test = Test::factory()->create();
        $answer = TestAnswer::factory()->create(['test_id' => $test->id]);

        $this->assertTrue($test->testAnswers->contains($answer));
    }

    public function test_calculate_score_with_correct_answers(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $question1 = Question::factory()->multipleChoice()->create();
        $correct1 = QuestionOption::factory()->correct()->create(['question_id' => $question1->id]);

        $question2 = Question::factory()->multipleChoice()->create();
        $correct2 = QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);

        $quiz->questions()->attach([$question1->id, $question2->id]);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);

        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question1->id,
            'option_id' => $correct1->id,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question2->id,
            'option_id' => $correct2->id,
        ]);

        $this->assertEquals(2, $test->calculateScore());
        $this->assertEquals(2, $test->score);
    }

    public function test_calculate_score_with_mixed_answers(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $question1 = Question::factory()->multipleChoice()->create();
        $correct1 = QuestionOption::factory()->correct()->create(['question_id' => $question1->id]);

        $question2 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        $wrong2 = QuestionOption::factory()->create(['question_id' => $question2->id, 'correct' => false]);

        $quiz->questions()->attach([$question1->id, $question2->id]);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);

        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question1->id,
            'option_id' => $correct1->id,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question2->id,
            'option_id' => $wrong2->id,
        ]);

        $this->assertEquals(1, $test->calculateScore());
    }

    public function test_calculate_score_with_no_correct_answers(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $wrong = QuestionOption::factory()->create(['question_id' => $question->id, 'correct' => false]);

        $quiz->questions()->attach($question);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);

        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'option_id' => $wrong->id,
        ]);

        $this->assertEquals(0, $test->calculateScore());
    }
}

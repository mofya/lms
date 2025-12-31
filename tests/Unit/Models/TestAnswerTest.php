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

class TestAnswerTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_answer_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $answer = TestAnswer::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $answer->user);
        $this->assertEquals($user->id, $answer->user->id);
    }

    public function test_test_answer_belongs_to_test(): void
    {
        $quiz = Quiz::factory()->create();
        $test = Test::factory()->create(['quiz_id' => $quiz->id]);
        $answer = TestAnswer::factory()->create(['test_id' => $test->id]);

        $this->assertInstanceOf(Test::class, $answer->test);
        $this->assertEquals($test->id, $answer->test->id);
    }

    public function test_test_answer_belongs_to_question(): void
    {
        $question = Question::factory()->create();
        $answer = TestAnswer::factory()->create(['question_id' => $question->id]);

        $this->assertInstanceOf(Question::class, $answer->question);
        $this->assertEquals($question->id, $answer->question->id);
    }

    public function test_test_answer_belongs_to_option(): void
    {
        $question = Question::factory()->create();
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);
        $answer = TestAnswer::factory()->create([
            'question_id' => $question->id,
            'option_id' => $option->id,
        ]);

        $this->assertInstanceOf(QuestionOption::class, $answer->option);
        $this->assertEquals($option->id, $answer->option->id);
    }

    public function test_correct_is_cast_to_boolean(): void
    {
        $answer = TestAnswer::factory()->create(['correct' => 1]);

        $answer->refresh();

        $this->assertIsBool($answer->correct);
        $this->assertTrue($answer->correct);
    }

    public function test_correct_false_is_cast_to_boolean(): void
    {
        $answer = TestAnswer::factory()->create(['correct' => 0]);

        $answer->refresh();

        $this->assertIsBool($answer->correct);
        $this->assertFalse($answer->correct);
    }

    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        $question = Question::factory()->create();
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $data = [
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'option_id' => $option->id,
            'user_answer' => 'My answer',
            'correct' => true,
        ];

        $answer = TestAnswer::create($data);

        $this->assertEquals($user->id, $answer->user_id);
        $this->assertEquals($test->id, $answer->test_id);
        $this->assertEquals($question->id, $answer->question_id);
        $this->assertEquals($option->id, $answer->option_id);
        $this->assertEquals('My answer', $answer->user_answer);
        $this->assertTrue($answer->correct);
    }

    public function test_factory_creates_valid_test_answer(): void
    {
        $answer = TestAnswer::factory()->create();

        $this->assertNotNull($answer->id);
        $this->assertNotNull($answer->user_id);
        $this->assertNotNull($answer->test_id);
        $this->assertNotNull($answer->question_id);
    }

    public function test_option_can_be_null_for_text_answers(): void
    {
        $answer = TestAnswer::factory()->create([
            'option_id' => null,
            'user_answer' => 'Text answer from user',
        ]);

        $this->assertNull($answer->option_id);
        $this->assertNull($answer->option);
        $this->assertEquals('Text answer from user', $answer->user_answer);
    }

    public function test_multiple_answers_can_belong_to_same_test(): void
    {
        $quiz = Quiz::factory()->create();
        $test = Test::factory()->create(['quiz_id' => $quiz->id]);

        $answer1 = TestAnswer::factory()->create(['test_id' => $test->id]);
        $answer2 = TestAnswer::factory()->create(['test_id' => $test->id]);
        $answer3 = TestAnswer::factory()->create(['test_id' => $test->id]);

        $this->assertCount(3, $test->testAnswers);
        $this->assertTrue($test->testAnswers->contains($answer1));
        $this->assertTrue($test->testAnswers->contains($answer2));
        $this->assertTrue($test->testAnswers->contains($answer3));
    }
}

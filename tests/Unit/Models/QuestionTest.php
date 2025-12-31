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

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_has_many_options(): void
    {
        $question = Question::factory()->create();
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $this->assertTrue($question->questionOptions->contains($option));
    }

    public function test_question_belongs_to_many_quizzes(): void
    {
        $question = Question::factory()->create();
        $quiz = Quiz::factory()->create();

        $quiz->questions()->attach($question);

        $this->assertTrue($question->quizzes->contains($quiz));
    }

    public function test_correct_options_returns_only_correct(): void
    {
        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->create(['question_id' => $question->id, 'correct' => false]);
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);

        $this->assertCount(1, $question->correctOptions);
        $this->assertTrue($question->correctOptions->contains($correctOption));
    }

    public function test_evaluate_answer_multiple_choice_correct(): void
    {
        $question = Question::factory()->multipleChoice()->create();
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'correct' => false]);

        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $quiz->questions()->attach($question);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        $answer = TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'option_id' => $correctOption->id,
        ]);

        $this->assertTrue($question->evaluateAnswer($answer));
    }

    public function test_evaluate_answer_multiple_choice_incorrect(): void
    {
        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $wrongOption = QuestionOption::factory()->create(['question_id' => $question->id, 'correct' => false]);

        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $quiz->questions()->attach($question);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        $answer = TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'option_id' => $wrongOption->id,
        ]);

        $this->assertFalse($question->evaluateAnswer($answer));
    }

    public function test_evaluate_answer_checkbox_all_correct(): void
    {
        $question = Question::factory()->checkbox()->create();
        $correct1 = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $correct2 = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'correct' => false]);

        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $quiz->questions()->attach($question);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        $answer = TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'user_answer' => json_encode([$correct1->id, $correct2->id]),
        ]);

        $this->assertTrue($question->evaluateAnswer($answer));
    }

    public function test_evaluate_answer_checkbox_missing_one(): void
    {
        $question = Question::factory()->checkbox()->create();
        $correct1 = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);

        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $quiz->questions()->attach($question);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        $answer = TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'user_answer' => json_encode([$correct1->id]), // Missing correct2
        ]);

        $this->assertFalse($question->evaluateAnswer($answer));
    }

    public function test_evaluate_answer_single_answer_correct(): void
    {
        $question = Question::factory()->singleAnswer('Paris')->create();

        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $quiz->questions()->attach($question);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        $answer = TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'user_answer' => 'Paris',
        ]);

        $this->assertTrue($question->evaluateAnswer($answer));
    }

    public function test_evaluate_answer_single_answer_case_insensitive(): void
    {
        $question = Question::factory()->singleAnswer('Paris')->create();

        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $quiz->questions()->attach($question);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        $answer = TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'user_answer' => 'PARIS',
        ]);

        $this->assertTrue($question->evaluateAnswer($answer));
    }

    public function test_evaluate_answer_single_answer_incorrect(): void
    {
        $question = Question::factory()->singleAnswer('Paris')->create();

        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $quiz->questions()->attach($question);

        $test = Test::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id]);
        $answer = TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'user_answer' => 'London',
        ]);

        $this->assertFalse($question->evaluateAnswer($answer));
    }
}

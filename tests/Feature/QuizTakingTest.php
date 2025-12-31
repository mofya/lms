<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\User;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTakingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_take_quiz(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $quiz = Quiz::factory()->published()->create(['course_id' => $course->id]);

        $question = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        $test = Test::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->assertDatabaseHas('tests', [
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);
    }

    public function test_quiz_score_is_calculated_correctly(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $question1 = Question::factory()->multipleChoice()->create();
        $correct1 = QuestionOption::factory()->correct()->create(['question_id' => $question1->id]);
        QuestionOption::factory()->create(['question_id' => $question1->id, 'correct' => false]);

        $question2 = Question::factory()->multipleChoice()->create();
        QuestionOption::factory()->correct()->create(['question_id' => $question2->id]);
        $wrong2 = QuestionOption::factory()->create(['question_id' => $question2->id, 'correct' => false]);

        $quiz->questions()->attach([$question1->id, $question2->id]);

        $test = Test::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        // Answer question 1 correctly
        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question1->id,
            'option_id' => $correct1->id,
        ]);

        // Answer question 2 incorrectly
        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question2->id,
            'option_id' => $wrong2->id,
        ]);

        $this->assertEquals(1, $test->score);
    }

    public function test_perfect_quiz_score_awards_bonus_xp(): void
    {
        $user = User::factory()->create(['xp_points' => 0]);
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->multipleChoice()->create();
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        $test = Test::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'option_id' => $correctOption->id,
        ]);

        // Award XP for quiz completion
        $xpService = new XpService();
        $xpService->awardQuizCompletion($user, $test->score, $quiz->questions()->count());

        $user->refresh();

        $expectedXp = XpService::XP_PER_QUIZ + XpService::XP_PERFECT_SCORE_BONUS;
        $this->assertEquals($expectedXp, $user->xp_points);
    }

    public function test_first_quiz_badge_is_awarded(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        Test::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        // Create badge AFTER test so observer doesn't auto-award
        $badge = Badge::factory()->forFirstQuiz()->create();

        $awarded = $badge->checkAndAward($user);

        $this->assertTrue($awarded);
        $this->assertTrue($user->badges()->where('badge_id', $badge->id)->exists());
    }

    public function test_perfect_score_badge_is_awarded(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->multipleChoice()->create();
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $quiz->questions()->attach($question);

        $test = Test::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'option_id' => $correctOption->id,
        ]);

        // Create badge AFTER test so observer doesn't auto-award
        $badge = Badge::factory()->forPerfectScore()->create();

        $awarded = $badge->checkAndAward($user);

        $this->assertTrue($awarded);
    }

    public function test_checkbox_question_requires_all_correct_options(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->checkbox()->create();
        $correct1 = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $correct2 = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'correct' => false]);

        $quiz->questions()->attach($question);

        $test = Test::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        // Only select one of the two correct options
        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'user_answer' => json_encode([$correct1->id]),
        ]);

        $this->assertEquals(0, $test->score);
    }

    public function test_single_answer_question_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->singleAnswer('Paris')->create();
        $quiz->questions()->attach($question);

        $test = Test::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);

        TestAnswer::factory()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'question_id' => $question->id,
            'user_answer' => 'PARIS',
        ]);

        $this->assertEquals(1, $test->score);
    }
}

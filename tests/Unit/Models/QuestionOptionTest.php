<?php

namespace Tests\Unit\Models;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_option_belongs_to_question(): void
    {
        $question = Question::factory()->create();
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $this->assertInstanceOf(Question::class, $option->question);
        $this->assertEquals($question->id, $option->question->id);
    }

    public function test_correct_is_cast_to_boolean(): void
    {
        $option = QuestionOption::factory()->create(['correct' => 1]);

        $option->refresh();

        $this->assertIsBool($option->correct);
        $this->assertTrue($option->correct);
    }

    public function test_correct_false_is_cast_to_boolean(): void
    {
        $option = QuestionOption::factory()->create(['correct' => 0]);

        $option->refresh();

        $this->assertIsBool($option->correct);
        $this->assertFalse($option->correct);
    }

    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $question = Question::factory()->create();
        $data = [
            'question_id' => $question->id,
            'option' => 'Sample answer option',
            'correct' => true,
        ];

        $option = QuestionOption::create($data);

        $this->assertEquals($question->id, $option->question_id);
        $this->assertEquals('Sample answer option', $option->option);
        $this->assertTrue($option->correct);
    }

    public function test_factory_creates_valid_question_option(): void
    {
        $option = QuestionOption::factory()->create();

        $this->assertNotNull($option->id);
        $this->assertNotNull($option->question_id);
        $this->assertNotNull($option->option);
    }

    public function test_factory_correct_state_creates_correct_option(): void
    {
        $option = QuestionOption::factory()->correct()->create();

        $this->assertTrue($option->correct);
    }

    public function test_question_option_can_be_updated(): void
    {
        $option = QuestionOption::factory()->create([
            'option' => 'Original text',
            'correct' => false,
        ]);

        $option->update([
            'option' => 'Updated text',
            'correct' => true,
        ]);

        $option->refresh();

        $this->assertEquals('Updated text', $option->option);
        $this->assertTrue($option->correct);
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\QuestionGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_generation_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $generation = QuestionGeneration::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $generation->user);
        $this->assertEquals($user->id, $generation->user->id);
    }

    public function test_prompt_params_is_cast_to_array(): void
    {
        $params = ['topic' => 'Mathematics', 'difficulty' => 'medium', 'count' => 5];
        $generation = QuestionGeneration::factory()->create([
            'prompt_params' => $params,
        ]);

        $generation->refresh();

        $this->assertIsArray($generation->prompt_params);
        $this->assertEquals($params, $generation->prompt_params);
    }

    public function test_questions_generated_is_cast_to_integer(): void
    {
        $generation = QuestionGeneration::factory()->create([
            'questions_generated' => '10',
        ]);

        $generation->refresh();

        $this->assertIsInt($generation->questions_generated);
        $this->assertEquals(10, $generation->questions_generated);
    }

    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $data = [
            'user_id' => $user->id,
            'provider' => 'openai',
            'prompt_params' => ['topic' => 'Science'],
            'questions_generated' => 5,
        ];

        $generation = QuestionGeneration::create($data);

        $this->assertEquals($user->id, $generation->user_id);
        $this->assertEquals('openai', $generation->provider);
        $this->assertEquals(['topic' => 'Science'], $generation->prompt_params);
        $this->assertEquals(5, $generation->questions_generated);
    }

    public function test_factory_creates_valid_question_generation(): void
    {
        $generation = QuestionGeneration::factory()->create();

        $this->assertNotNull($generation->id);
        $this->assertNotNull($generation->user_id);
        $this->assertNotNull($generation->provider);
    }
}

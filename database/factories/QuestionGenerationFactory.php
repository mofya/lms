<?php

namespace Database\Factories;

use App\Models\QuestionGeneration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionGenerationFactory extends Factory
{
    protected $model = QuestionGeneration::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'openai',
            'prompt_params' => [
                'topic' => fake()->words(3, true),
                'difficulty' => 'medium',
                'question_count' => 5,
            ],
            'questions_generated' => 5,
        ];
    }
}

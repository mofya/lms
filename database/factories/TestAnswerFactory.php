<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestAnswerFactory extends Factory
{
    protected $model = TestAnswer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'test_id' => Test::factory(),
            'question_id' => Question::factory(),
            'option_id' => null,
            'user_answer' => null,
            'correct' => false,
        ];
    }

    public function withOption(QuestionOption $option): static
    {
        return $this->state(fn (array $attributes) => [
            'option_id' => $option->id,
        ]);
    }

    public function withAnswer(string $answer): static
    {
        return $this->state(fn (array $attributes) => [
            'user_answer' => $answer,
        ]);
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'correct' => true,
        ]);
    }
}

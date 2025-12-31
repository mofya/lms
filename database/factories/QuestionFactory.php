<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'question_text' => fake()->sentence() . '?',
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'code_snippet' => null,
            'answer_explanation' => fake()->paragraph(),
            'more_info_link' => fake()->optional()->url(),
            'correct_answer' => null,
        ];
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Question::TYPE_MULTIPLE_CHOICE,
        ]);
    }

    public function checkbox(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Question::TYPE_CHECKBOX,
        ]);
    }

    public function singleAnswer(string $answer = 'correct'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Question::TYPE_SINGLE_ANSWER,
            'correct_answer' => $answer,
        ]);
    }

    public function withCodeSnippet(): static
    {
        return $this->state(fn (array $attributes) => [
            'code_snippet' => "<?php\necho 'Hello World';",
        ]);
    }
}

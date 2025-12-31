<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Rubric;
use Illuminate\Database\Eloquent\Factories\Factory;

class RubricFactory extends Factory
{
    protected $model = Rubric::class;

    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'type' => 'structured',
            'freeform_text' => null,
        ];
    }

    public function structured(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'structured',
        ]);
    }

    public function freeform(string $text = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'freeform',
            'freeform_text' => $text ?? fake()->paragraphs(2, true),
        ]);
    }
}

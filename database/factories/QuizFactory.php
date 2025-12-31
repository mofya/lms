<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'course_id' => Course::factory(),
            'is_published' => false,
            'start_time' => null,
            'end_time' => null,
            'duration_per_question' => null,
            'total_duration' => null,
            'attempts_allowed' => 3,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
    }

    public function withDuration(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'total_duration' => $minutes,
        ]);
    }

    public function withAttempts(int $attempts): static
    {
        return $this->state(fn (array $attributes) => [
            'attempts_allowed' => $attempts,
        ]);
    }
}

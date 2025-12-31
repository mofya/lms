<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestFactory extends Factory
{
    protected $model = Test::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'result' => null,
            'ip_address' => fake()->ipv4(),
            'time_spent' => null,
            'attempt_number' => 1,
            'started_at' => now(),
            'submitted_at' => null,
            'correct_count' => null,
            'wrong_count' => null,
        ];
    }

    /**
     * Indicate the test is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now()->subMinutes(5),
            'submitted_at' => null,
        ]);
    }

    /**
     * Indicate the test is submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now(),
            'result' => fake()->numberBetween(0, 10),
            'correct_count' => fake()->numberBetween(0, 10),
            'wrong_count' => fake()->numberBetween(0, 10),
        ]);
    }

    /**
     * Set a specific attempt number.
     */
    public function attemptNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'attempt_number' => $number,
        ]);
    }
}

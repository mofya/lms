<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentSubmissionFactory extends Factory
{
    protected $model = AssignmentSubmission::class;

    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'user_id' => User::factory(),
            'attempt_number' => 1,
            'content' => fake()->paragraphs(3, true),
            'file_path' => null,
            'file_name' => null,
            'status' => 'draft',
            'submitted_at' => null,
            'is_late' => false,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
            'is_late' => true,
        ]);
    }

    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'graded',
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function withFile(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'submissions/' . fake()->uuid() . '.pdf',
            'file_name' => fake()->word() . '.pdf',
        ]);
    }
}

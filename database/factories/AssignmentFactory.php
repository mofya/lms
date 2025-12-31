<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'instructions' => fake()->paragraphs(2, true),
            'type' => 'file',
            'allowed_file_types' => ['pdf', 'doc', 'docx'],
            'max_file_size_mb' => 10,
            'max_submissions' => 3,
            'max_points' => 100,
            'available_from' => null,
            'due_at' => null,
            'late_due_at' => null,
            'late_penalty_percent' => 10,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'available_from' => now()->subDay(),
            'due_at' => now()->addWeek(),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'available_from' => now()->subWeek(),
            'due_at' => now()->subDay(),
        ]);
    }

    public function withLatePeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'available_from' => now()->subWeek(),
            'due_at' => now()->subDay(),
            'late_due_at' => now()->addDays(2),
            'late_penalty_percent' => 20,
        ]);
    }
}

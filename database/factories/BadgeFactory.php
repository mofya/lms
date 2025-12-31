<?php

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'icon' => 'heroicon-o-star',
            'color' => fake()->hexColor(),
            'trigger_type' => 'first_quiz',
            'trigger_value' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forFirstQuiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'first_quiz',
        ]);
    }

    public function forFirstAssignment(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'first_assignment',
        ]);
    }

    public function forCourseCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'course_completed',
        ]);
    }

    public function forPerfectScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'perfect_score',
        ]);
    }

    public function forStreak(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'streak',
            'trigger_value' => $days,
        ]);
    }

    public function forQuizzesCompleted(int $count = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'quizzes_completed',
            'trigger_value' => $count,
        ]);
    }

    public function forAssignmentsCompleted(int $count = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'assignments_completed',
            'trigger_value' => $count,
        ]);
    }

    public function forDiscussionsParticipated(int $count = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'discussions_participated',
            'trigger_value' => $count,
        ]);
    }
}

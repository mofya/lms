<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'quiz_average' => null,
            'assignment_average' => null,
            'participation_score' => null,
            'final_grade' => null,
            'quiz_weight' => 40,
            'assignment_weight' => 50,
            'participation_weight' => 10,
            'total_quizzes' => 0,
            'completed_quizzes' => 0,
            'total_assignments' => 0,
            'completed_assignments' => 0,
            'calculated_at' => null,
        ];
    }

    public function calculated(): static
    {
        return $this->state(fn (array $attributes) => [
            'quiz_average' => fake()->randomFloat(2, 60, 100),
            'assignment_average' => fake()->randomFloat(2, 60, 100),
            'participation_score' => fake()->randomFloat(2, 50, 100),
            'final_grade' => fake()->randomFloat(2, 60, 100),
            'calculated_at' => now(),
        ]);
    }
}

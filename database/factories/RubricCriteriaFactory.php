<?php

namespace Database\Factories;

use App\Models\Rubric;
use App\Models\RubricCriteria;
use Illuminate\Database\Eloquent\Factories\Factory;

class RubricCriteriaFactory extends Factory
{
    protected $model = RubricCriteria::class;

    protected static int $position = 1;

    public function definition(): array
    {
        return [
            'rubric_id' => Rubric::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'max_points' => fake()->numberBetween(10, 25),
            'position' => self::$position++,
        ];
    }
}

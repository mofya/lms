<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;
    // Initialize a static counter for the position
    protected static $position = 1;
    public function definition()
    {
        $faker = app(\Faker\Generator::class);

        return [
            'title' => $faker->promptAI('Generate a lesson title on web development'),
            'lesson_text' => $faker->promptAI('Provide a brief lesson text for a web development topic'),
            'course_id' => Course::factory(),
            'position' => self::$position++,
        ];
    }

    public function published(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => true,
            ];
        });
    }
}

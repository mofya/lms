<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    protected static int $position = 1;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'lesson_text' => fake()->paragraphs(3, true),
            'course_id' => Course::factory(),
            'position' => self::$position++,
            'is_published' => false,
            'type' => Lesson::TYPE_TEXT,
            'video_url' => null,
            'duration_seconds' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function video(string $url = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Lesson::TYPE_VIDEO,
            'video_url' => $url ?? 'https://example.com/video.mp4',
            'duration_seconds' => fake()->numberBetween(300, 3600),
        ]);
    }

    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}

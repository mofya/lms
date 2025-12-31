<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(2, true),
            'is_pinned' => false,
            'published_at' => null,
            'expires_at' => null,
        ];
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => null,
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now()->subHour(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now()->addDay(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now()->subWeek(),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function withExpiration(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addWeek(),
        ]);
    }
}

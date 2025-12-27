<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition()
    {
        $faker = app(\Faker\Generator::class);

        return [
            'title' => $faker->promptAI('Generate a course title on web development'),
            'description' => $faker->promptAI('Provide a brief description for a web development course'),
            'lecturer_id' => User::where('is_admin', true)->inRandomOrder()->first()?->id ?? User::factory()->create(['is_admin' => true])->id,
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

    public function configure(): static
    {
        return $this->afterCreating(function (Course $course) {
            // Check if the storage disk is set up correctly
            $images = collect(Storage::disk('public')->files('demo-images'));

            if ($images->isNotEmpty()) {
                // Ensure media library methods are compatible with Filament v3
                $course->addMedia(storage_path("app/public/" . $images->random()))
                    ->preservingOriginal()
                    ->toMediaCollection('featured_image');
            } else {
                \Log::warning('No images found in storage/app/public/demo-images directory.');
            }
        });
    }
}

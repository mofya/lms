<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name'     => 'Test User',
            'email'    => 'admin@admin.com',
            'is_admin' => true,
        ]);

        User::factory()
            ->create([
                'name' => 'Student',
                'email' => 'student@example.com',
            ]);

        Course::factory(3)
            ->published()
            ->has(Lesson::factory(3)->published())
            ->create();

        $this->call([
            QuizSeeder::class,
        ]);
    }
}
